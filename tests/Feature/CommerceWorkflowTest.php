<?php
namespace Tests\Feature;
use App\Contracts\CheckoutGateway;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Stripe\WebhookSignature;
use Tests\TestCase;
class CommerceWorkflowTest extends TestCase
{
 use RefreshDatabase;
 private function catalog(): array
 {
  Storage::fake('local');$seller=User::factory()->create();SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Commerce Studio','username'=>'commerce-studio','country'=>'AE','biography'=>'Seller','status'=>'approved']);$category=Category::create(['name'=>'Apps','slug'=>'apps']);$regular=LicenseType::create(['name'=>'Regular License','slug'=>'regular','description'=>'Regular']);$extended=LicenseType::create(['name'=>'Extended License','slug'=>'extended','description'=>'Extended','allows_paid_end_product'=>true]);$product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Project Manager','slug'=>'project-manager','short_description'=>'Manage projects.','description'=>str_repeat('Complete project management. ',4),'regular_price'=>'40.00','extended_price'=>'160.00','status'=>ProductStatus::Published,'published_at'=>now()]);$version=$product->versions()->create(['version_number'=>'1.0.0','release_title'=>'Stable','status'=>ProductVersionStatus::Published,'published_at'=>now()]);Storage::disk('local')->put('products/project.zip','private archive');$version->files()->create(['disk'=>'local','path'=>'products/project.zip','original_name'=>'project.zip','mime_type'=>'application/zip','extension'=>'zip','size'=>15,'checksum'=>hash('sha256','private archive'),'scan_status'=>'clean']);return compact('seller','regular','extended','product','version');
 }
 private function fakeGateway(): void{$this->app->bind(CheckoutGateway::class,fn()=>new class implements CheckoutGateway{public function createCheckout(\App\Models\Order $order):array{return ['id'=>'cs_test_'.$order->id,'url'=>'https://checkout.stripe.test/session/'.$order->id];}});}
 public function test_checkout_snapshots_server_prices_without_granting_access(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['extended']->id])->assertRedirect('/cart');$response=$this->post('/checkout');$response->assertRedirectContains('checkout.stripe.test');$order=$customer->orders()->with('items')->firstOrFail();$this->assertEquals(160.00,$order->total);$this->assertEquals(160.00,$order->items->first()->unit_price);$this->assertSame('pending',$order->payment_status);$this->assertDatabaseCount('licenses',0);
 }
 public function test_fulfilment_is_idempotent_and_issues_one_license(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);$this->post('/checkout');$order=$customer->orders()->firstOrFail();$service=app(PaymentFulfillmentService::class);$service->fulfill($order->id,'pi_unique');$service->fulfill($order->id,'pi_unique');$this->assertDatabaseCount('payments',1);$this->assertDatabaseCount('licenses',1);$this->assertSame('paid',$order->fresh()->payment_status);$this->assertSame(1,$data['product']->fresh()->sales_count);
 }
 public function test_signed_download_requires_paid_active_license_and_is_logged(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);$this->post('/checkout');$order=$customer->orders()->firstOrFail();app(PaymentFulfillmentService::class)->fulfill($order->id,'pi_download');$license=$customer->licenses()->firstOrFail();$url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$license]);$this->get($url)->assertOk();$this->assertDatabaseHas('downloads',['license_id'=>$license->id,'user_id'=>$customer->id]);
 }
 public function test_signed_stripe_webhook_is_stored_and_replay_safe(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);$this->post('/checkout');$order=$customer->orders()->firstOrFail();config(['services.stripe.webhook_secret'=>'whsec_test']);$payload=json_encode(['id'=>'evt_checkout_1','object'=>'event','type'=>'checkout.session.completed','data'=>['object'=>['id'=>'cs_test','object'=>'checkout.session','payment_intent'=>'pi_webhook','metadata'=>['order_id'=>(string)$order->id]]]]);$timestamp=time();$signature='t='.$timestamp.',v1='.hash_hmac('sha256',$timestamp.'.'.$payload,'whsec_test');$server=['HTTP_STRIPE_SIGNATURE'=>$signature,'CONTENT_TYPE'=>'application/json'];$this->call('POST','/stripe/webhook',[],[],[],$server,$payload)->assertOk();$this->call('POST','/stripe/webhook',[],[],[],$server,$payload)->assertOk();$this->assertDatabaseCount('stripe_webhook_events',1);$this->assertDatabaseCount('payments',1);$this->assertDatabaseCount('licenses',1);
 }
}
