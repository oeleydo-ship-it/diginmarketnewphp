<?php
namespace Tests\Feature;
use App\Contracts\RefundGateway;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\SellerWallet;
use App\Models\User;
use App\Services\DisputeService;
use App\Services\PaymentWebhookService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Regressions around money that moves twice, or entitlements that disappear when they shouldn't. */
class RefundAndWebhookResilienceTest extends TestCase
{
 use RefreshDatabase;

 /** Two-item paid order (two different products), each with a licence and a downloadable file. */
 private function order(): array
 {
  Storage::fake('local');
  $seller=User::factory()->create();
  $buyer=User::factory()->create();
  $category=Category::create(['name'=>'Refund Apps','slug'=>'refund-apps']);
  $type=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);
  $order=Order::create(['number'=>'DM-MULTI-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>200,'tax'=>20,'total'=>220,'payment_status'=>'paid','status'=>'completed','payment_provider'=>'bank_transfer','paid_at'=>now()]);
  $order->payments()->create(['provider'=>'bank_transfer','provider_payment_id'=>'bt:multi-1','amount'=>220,'currency'=>'USD','status'=>'succeeded','paid_at'=>now()]);
  SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','pending_balance'=>160,'lifetime_earnings'=>160]);
  $lines=[];
  foreach (['alpha','beta'] as $index=>$slug) {
   $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>ucfirst($slug).' Kit','slug'=>$slug.'-kit','short_description'=>'Kit','description'=>str_repeat('A complete kit. ',5),'regular_price'=>100,'status'=>ProductStatus::Published,'published_at'=>now()]);
   $version=$product->versions()->create(['version_number'=>'1.0.0','release_title'=>'Stable','status'=>ProductVersionStatus::Published,'published_at'=>now()]);
   Storage::disk('local')->put($slug.'.zip','archive');
   $version->files()->create(['disk'=>'local','path'=>$slug.'.zip','original_name'=>$slug.'.zip','mime_type'=>'application/zip','extension'=>'zip','size'=>7,'checksum'=>hash('sha256',$slug),'scan_status'=>'clean']);
   $item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'product_version_id'=>$version->id,'license_type_id'=>$type->id,'product_title'=>$product->title,'seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>100,'tax'=>10,'platform_commission'=>20,'seller_earning'=>80,'total'=>100]);
   $lines[$index]=['product'=>$product,'item'=>$item,'license'=>License::create(['license_key'=>'DM-MULTI-'.$slug,'product_id'=>$product->id,'product_version_id'=>$version->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'license_type_id'=>$type->id,'status'=>'active','support_expires_at'=>now()->addMonths(6)])];
  }
  return compact('seller','buyer','order','lines','type');
 }

 public function test_refunding_one_item_leaves_the_rest_of_the_order_downloadable(): void
 {
  $data=$this->order();
  $request=app(RefundService::class)->request($data['buyer'],$data['lines'][0]['item']->load('order'),['reason'=>'broken','description'=>str_repeat('It does not work at all. ',2)]);
  app(RefundService::class)->approve($request,User::factory()->create(),100);
  $this->assertSame('partially_refunded',$data['order']->fresh()->payment_status);
  $this->assertSame('refunded',$data['lines'][0]['license']->fresh()->status);
  // The untouched sibling item must keep working: download, licence API and support all used to
  // break the moment the order left the 'paid' state.
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$data['lines'][1]['license']->id]);
  $this->actingAs($data['buyer'])->get($url)->assertOk();
  $this->postJson('/api/v1/licenses/verify',['license_key'=>'DM-MULTI-beta'])->assertOk()->assertJsonPath('valid',true);
  $this->postJson('/api/v1/licenses/verify',['license_key'=>'DM-MULTI-alpha'])->assertStatus(422);
  $this->actingAs($data['buyer'])->get('/purchases/'.$data['order']->id.'/invoice')->assertOk();
 }

 public function test_refunding_every_item_marks_the_order_refunded(): void
 {
  $data=$this->order();
  $admin=User::factory()->create();
  foreach ($data['lines'] as $line) {
   $request=app(RefundService::class)->request($data['buyer'],$line['item']->load('order'),['reason'=>'broken','description'=>str_repeat('It does not work at all. ',2)]);
   app(RefundService::class)->approve($request,$admin,100);
  }
  $this->assertSame('refunded',$data['order']->fresh()->payment_status);
 }

 public function test_refund_request_covers_the_tax_the_buyer_paid(): void
 {
  $data=$this->order();
  $request=app(RefundService::class)->request($data['buyer'],$data['lines'][0]['item']->load('order'),['reason'=>'broken','description'=>str_repeat('It does not work at all. ',2)]);
  // The line was 100.00 net plus 10.00 tax; refunding only the net short-changed the buyer.
  $this->assertEquals(110.00,(float)$request->requested_amount);
 }

 public function test_a_second_approval_never_refunds_at_the_provider_twice(): void
 {
  $data=$this->order();
  $counter=new class {public int $calls=0;};
  $this->app->bind(RefundGateway::class,fn()=>new class($counter) implements RefundGateway {
   public function __construct(private object $counter){}
   public function refund(Payment $payment,float $amount): array {$this->counter->calls++;return ['id'=>'re_'.$this->counter->calls,'status'=>'succeeded'];}
  });
  $request=app(RefundService::class)->request($data['buyer'],$data['lines'][0]['item']->load('order'),['reason'=>'broken','description'=>str_repeat('It does not work at all. ',2)]);
  $admin=User::factory()->create();
  app(RefundService::class)->approve($request,$admin,100);
  app(RefundService::class)->approve($request->fresh(),$admin,100);
  $this->assertSame(1,$counter->calls);
  $this->assertSame(1,RefundRequest::whereKey($request->id)->where('status','refunded')->count());
  $this->assertDatabaseCount('wallet_transactions',1);
  $this->assertDatabaseHas('audit_logs',['action'=>'refund.approved','entity_id'=>$request->id]);
 }

 public function test_duplicate_refund_requests_are_rejected_with_a_message(): void
 {
  $data=$this->order();
  app(RefundService::class)->request($data['buyer'],$data['lines'][0]['item']->load('order'),['reason'=>'broken','description'=>str_repeat('It does not work at all. ',2)]);
  $this->expectException(\Illuminate\Validation\ValidationException::class);
  app(RefundService::class)->request($data['buyer'],$data['lines'][0]['item']->fresh()->load('order'),['reason'=>'broken','description'=>str_repeat('Still does not work. ',2)]);
 }

 public function test_upholding_one_dispute_does_not_revoke_the_other_items(): void
 {
  $data=$this->order();
  $dispute=app(DisputeService::class)->open($data['buyer'],$data['lines'][0]['item']->load('order','license'),['type'=>'quality','description'=>str_repeat('The build crashes on start. ',2)]);
  app(DisputeService::class)->uphold($dispute->load('orderItem.order','orderItem.license'),User::factory()->create(),100,'Chargeback confirmed.');
  $this->assertSame('partially_refunded',$data['order']->fresh()->payment_status);
  $this->assertSame('revoked',$data['lines'][0]['license']->fresh()->status);
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$data['lines'][1]['license']->id]);
  $this->actingAs($data['buyer'])->get($url)->assertOk();
 }

 public function test_a_failed_webhook_delivery_is_retried_on_redelivery(): void
 {
  $data=$this->order();
  $order=Order::create(['number'=>'DM-WH-1','user_id'=>$data['buyer']->id,'currency'=>'USD','subtotal'=>10,'total'=>10,'payment_status'=>'pending','status'=>'awaiting_payment','payment_provider'=>'bank_transfer']);
  PaymentWebhookEvent::create(['provider'=>'stripe','event_id'=>'evt_retry_1','event_type'=>'checkout.session.completed','payload'=>['seen'=>true],'processing_status'=>'failed','error_message'=>'Deadlock found','retry_count'=>1]);
  config(['services.stripe.webhook_secret'=>'whsec_test']);
  $payload=json_encode(['id'=>'evt_retry_1','object'=>'event','type'=>'checkout.session.completed','data'=>['object'=>['id'=>'cs_retry','object'=>'checkout.session','payment_intent'=>'pi_retry','metadata'=>['order_id'=>(string)$order->id]]]]);
  $timestamp=time();
  $server=['HTTP_STRIPE_SIGNATURE'=>'t='.$timestamp.',v1='.hash_hmac('sha256',$timestamp.'.'.$payload,'whsec_test'),'CONTENT_TYPE'=>'application/json'];
  $this->call('POST','/stripe/webhook',[],[],[],$server,$payload)->assertOk();
  // Previously the redelivery was dropped because the row already existed, so the paid order stayed
  // unfulfilled forever.
  $this->assertSame('paid',$order->fresh()->payment_status);
  $this->assertSame('processed',PaymentWebhookEvent::where('event_id','evt_retry_1')->value('processing_status'));
  $this->assertDatabaseCount('payment_webhook_events',1);
 }

 public function test_an_already_processed_webhook_is_still_ignored(): void
 {
  $data=$this->order();
  $order=Order::create(['number'=>'DM-WH-2','user_id'=>$data['buyer']->id,'currency'=>'USD','subtotal'=>10,'total'=>10,'payment_status'=>'pending','status'=>'awaiting_payment','payment_provider'=>'bank_transfer']);
  PaymentWebhookEvent::create(['provider'=>'stripe','event_id'=>'evt_done_1','event_type'=>'checkout.session.completed','payload'=>[],'processing_status'=>'processed','processed_at'=>now()]);
  config(['services.stripe.webhook_secret'=>'whsec_test']);
  $payload=json_encode(['id'=>'evt_done_1','object'=>'event','type'=>'checkout.session.completed','data'=>['object'=>['id'=>'cs_done','object'=>'checkout.session','payment_intent'=>'pi_done','metadata'=>['order_id'=>(string)$order->id]]]]);
  $timestamp=time();
  $server=['HTTP_STRIPE_SIGNATURE'=>'t='.$timestamp.',v1='.hash_hmac('sha256',$timestamp.'.'.$payload,'whsec_test'),'CONTENT_TYPE'=>'application/json'];
  $this->call('POST','/stripe/webhook',[],[],[],$server,$payload)->assertOk();
  $this->assertSame('pending',$order->fresh()->payment_status);
  $this->assertDatabaseMissing('payments',['provider_payment_id'=>'pi_done']);
 }
}
