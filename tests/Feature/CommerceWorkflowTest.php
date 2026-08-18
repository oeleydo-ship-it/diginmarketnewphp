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
 private function fakeGateway(): void{$this->app->bind(\App\Services\Gateways\StripeCheckoutGateway::class,fn()=>new class extends \App\Services\Gateways\StripeCheckoutGateway{public function isConfigured():bool{return true;}public function createCheckout(\App\Models\Order $order):array{return ['id'=>'cs_test_'.$order->id,'url'=>'https://checkout.stripe.test/session/'.$order->id];}});}
 public function test_checkout_snapshots_server_prices_without_granting_access(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['extended']->id])->assertRedirect('/cart');$this->get('/cart')->assertOk();$response=$this->post('/checkout');$response->assertRedirectContains('checkout.stripe.test');$order=$customer->orders()->with('items')->firstOrFail();$this->assertEquals(160.00,$order->total);$this->assertEquals(160.00,$order->items->first()->unit_price);$this->assertSame('pending',$order->payment_status);$this->assertDatabaseCount('licenses',0);
 }
 public function test_fulfilment_is_idempotent_and_issues_one_license(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);$this->post('/checkout');$order=$customer->orders()->firstOrFail();$service=app(PaymentFulfillmentService::class);$service->fulfill($order->id,'pi_unique');$service->fulfill($order->id,'pi_unique');  $this->assertDatabaseCount('payments',1);$this->assertDatabaseCount('licenses',1);$this->assertDatabaseCount('cart_items',0);$this->assertSame('paid',$order->fresh()->payment_status);$this->assertSame(1,$data['product']->fresh()->sales_count);
 }
 public function test_signed_download_requires_paid_active_license_and_is_logged(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);$this->post('/checkout');$order=$customer->orders()->firstOrFail();app(PaymentFulfillmentService::class)->fulfill($order->id,'pi_download');$license=$customer->licenses()->firstOrFail();$url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$license]);$this->get($url)->assertOk();$this->assertDatabaseHas('downloads',['license_id'=>$license->id,'user_id'=>$customer->id]);
 }
 public function test_signed_stripe_webhook_is_stored_and_replay_safe(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();$this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);$this->post('/checkout');$order=$customer->orders()->firstOrFail();config(['services.stripe.webhook_secret'=>'whsec_test']);$payload=json_encode(['id'=>'evt_checkout_1','object'=>'event','type'=>'checkout.session.completed','data'=>['object'=>['id'=>'cs_test','object'=>'checkout.session','payment_intent'=>'pi_webhook','metadata'=>['order_id'=>(string)$order->id]]]]);$timestamp=time();$signature='t='.$timestamp.',v1='.hash_hmac('sha256',$timestamp.'.'.$payload,'whsec_test');$server=['HTTP_STRIPE_SIGNATURE'=>$signature,'CONTENT_TYPE'=>'application/json'];  $this->call('POST','/stripe/webhook',[],[],[],$server,$payload)->assertOk();$this->call('POST','/stripe/webhook',[],[],[],$server,$payload)->assertOk();$this->assertDatabaseCount('payment_webhook_events',1);$this->assertDatabaseCount('payments',1);$this->assertDatabaseCount('licenses',1);$this->assertDatabaseCount('cart_items',0);
 }
 public function test_fulfilment_clears_cart_and_owner_can_buy_another_license(): void
 {
  $data=$this->catalog();$this->fakeGateway();$customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  $this->assertDatabaseCount('cart_items',1);
  $this->post('/checkout');
  $this->assertDatabaseCount('cart_items',1);
  app(PaymentFulfillmentService::class)->fulfill($customer->orders()->firstOrFail()->id,'pi_cart_clear');
  $this->assertDatabaseCount('cart_items',0);
  $this->get('/cart')->assertOk()->assertSee('Your cart is empty.')->assertDontSee('Proceed to Payment');
  $this->get('/products/project-manager')->assertOk()
   ->assertSee('You already have a license')
   ->assertSee('Buy another license')
   ->assertSee('Regular License')
   ->assertSee('name="license_type_id"', false);
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id])->assertRedirect('/cart');
  $this->assertDatabaseCount('cart_items',1);
  $this->get('/cart')->assertOk()->assertSee('Project Manager')->assertSee('You already own a license for this product')->assertSee('Proceed to Payment');
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['extended']->id])->assertRedirect('/cart');
  $this->assertDatabaseCount('cart_items',1);
  $this->assertDatabaseHas('cart_items',['product_id'=>$data['product']->id,'license_type_id'=>$data['extended']->id]);
 }
 public function test_owned_product_stays_in_cart_until_paid(): void
 {
  $data=$this->catalog();$this->fakeGateway();
  $other=Product::create(['seller_id'=>$data['seller']->id,'category_id'=>$data['product']->category_id,'title'=>'Other App','slug'=>'other-app','short_description'=>'Other.','description'=>str_repeat('Other product details. ',4),'regular_price'=>'15.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/checkout');
  app(PaymentFulfillmentService::class)->fulfill($customer->orders()->firstOrFail()->id,'pi_owned_stale');
  $cart=$customer->cart()->firstOrFail();
  $cart->items()->create(['product_id'=>$data['product']->id,'license_type_id'=>$data['regular']->id,'unit_price'=>40,'tax'=>0,'discount'=>0,'total'=>40]);
  $this->post('/cart/'.$other->id,['license_type_id'=>$data['regular']->id])->assertRedirect('/cart');
  $this->get('/cart')->assertOk()->assertSee('Other App')->assertSee('Project Manager')->assertSee('You already own a license for this product')->assertSee('Proceed to Payment');
  $this->assertDatabaseCount('cart_items',2);
  $this->get('/')->assertOk();
  $this->assertSame(2,$customer->fresh()->cartItemCount());
 }
}
