<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\Gateways\StripeCheckoutGateway;
use App\Services\PaymentFulfillmentService;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class PaymentGatewayTest extends TestCase
{
 use RefreshDatabase;
 private function product(): Product
 {
  $seller=User::factory()->create();SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Studio','username'=>'studio','country'=>'AE','biography'=>'x','status'=>'approved']);
  $category=Category::create(['name'=>'Apps','slug'=>'apps']);LicenseType::create(['name'=>'Regular License','slug'=>'regular','description'=>'r']);
  return Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Kit','slug'=>'kit','short_description'=>'A kit.','description'=>str_repeat('Kit. ',5),'regular_price'=>'40.00','extended_price'=>'160.00','status'=>\App\Enums\ProductStatus::Published,'published_at'=>now()]);
 }
 private function fakeStripe(): void{$this->app->bind(StripeCheckoutGateway::class,fn()=>new class extends StripeCheckoutGateway{public function isConfigured():bool{return true;}public function createCheckout(Order $order):array{return ['id'=>'cs_'.$order->id,'url'=>'https://checkout.stripe.test/'.$order->id];}});}

 public function test_only_enabled_and_configured_gateways_are_offered(): void
 {
  config(['services.paypal.client_id'=>'id','services.paypal.secret'=>'sk']);
  Setting::put('payments.paypal.enabled','1','payments');
  // razorpay enabled but has no credentials, so it must not be offered.
  Setting::put('payments.razorpay.enabled','1','payments');
  $this->fakeStripe();
  $keys=app(PaymentGatewayManager::class)->availableKeysFor('USD');
  $this->assertContains('stripe',$keys);
  $this->assertContains('paypal',$keys);
  $this->assertNotContains('razorpay',$keys);
 }

 public function test_currency_filter_hides_region_locked_gateways(): void
 {
  config(['services.razorpay.key'=>'k','services.razorpay.secret'=>'s']);
  Setting::put('payments.razorpay.enabled','1','payments');
  $this->fakeStripe();
  $manager=app(PaymentGatewayManager::class);
  $this->assertContains('razorpay',$manager->availableKeysFor('INR'));
  $this->assertNotContains('razorpay',$manager->availableKeysFor('USD'));
 }

 public function test_success_page_fulfils_immediately_when_provider_verifies_payment(): void
 {
  // Gateway whose synchronous verification reports the order as paid (as Stripe's API would).
  $this->app->bind(StripeCheckoutGateway::class,fn()=>new class extends StripeCheckoutGateway{public function isConfigured():bool{return true;}public function createCheckout(Order $order):array{return ['id'=>'cs_'.$order->id,'url'=>'https://checkout.stripe.test/'.$order->id];}public function verifyReturn(Order $order):?array{return ['payment_id'=>'pi_return_'.$order->id,'payload'=>['source'=>'return_verification']];}});
  $product=$this->product();$customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$product->id,['license_type_id'=>LicenseType::first()->id]);
  $this->post('/checkout',['payment_provider'=>'stripe']);
  $order=$customer->orders()->firstOrFail();
  $this->assertSame('pending',$order->payment_status);
  // Landing on the success page verifies with the provider and unlocks the purchase at once.
  $this->get('/checkout/'.$order->id.'/success')->assertOk()->assertSee('Payment Successful');
  $this->assertSame('paid',$order->fresh()->payment_status);
  $this->assertDatabaseCount('licenses',1);
  // Idempotent: revisiting (or a late webhook) cannot double-fulfil.
  $this->get('/checkout/'.$order->id.'/success')->assertOk();
  $this->assertDatabaseCount('payments',1);
 }

 public function test_success_page_stays_pending_when_provider_has_not_confirmed(): void
 {
  $this->app->bind(StripeCheckoutGateway::class,fn()=>new class extends StripeCheckoutGateway{public function isConfigured():bool{return true;}public function createCheckout(Order $order):array{return ['id'=>'cs_'.$order->id,'url'=>'https://checkout.stripe.test/'.$order->id];}public function verifyReturn(Order $order):?array{return null;}});
  $product=$this->product();$customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$product->id,['license_type_id'=>LicenseType::first()->id]);
  $this->post('/checkout',['payment_provider'=>'stripe']);
  $order=$customer->orders()->firstOrFail();
  $this->get('/checkout/'.$order->id.'/success')->assertOk()->assertSee('Confirming Your Payment');
  $this->assertSame('pending',$order->fresh()->payment_status);
  $this->assertDatabaseCount('licenses',0);
 }

 public function test_purchase_page_renders_for_multi_item_orders(): void
 {
  // Two items matter: the lazy-loading guard only throws on collections of ≥2, so a
  // single-item fixture would pass while a real two-product order 500s.
  $this->fakeStripe();
  $first=$this->product();
  $second=Product::create(['seller_id'=>$first->seller_id,'category_id'=>$first->category_id,'title'=>'Kit Two','slug'=>'kit-two','short_description'=>'Second kit.','description'=>str_repeat('Kit two. ',5),'regular_price'=>'25.00','support_extension_price'=>'9.00','status'=>\App\Enums\ProductStatus::Published,'published_at'=>now()]);
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$first->id,['license_type_id'=>LicenseType::first()->id]);
  $this->post('/cart/'.$second->id,['license_type_id'=>LicenseType::first()->id]);
  $this->post('/checkout',['payment_provider'=>'stripe']);
  $order=$customer->orders()->firstOrFail();
  app(PaymentFulfillmentService::class)->fulfill($order->id,'pi_multi');
  $this->get('/purchases/'.$order->id)->assertOk()->assertSee('Buy 6-month addon')->assertSee('6 months extra updates & support');
 }

 public function test_admin_orders_directory_lists_ordered_product_titles(): void
 {
  $this->fakeStripe();
  $product=$this->product();
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$product->id,['license_type_id'=>LicenseType::first()->id]);
  $this->post('/checkout',['payment_provider'=>'stripe']);
  $admin=User::factory()->create();
  $admin->roles()->attach(\App\Models\Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $this->actingAs($admin)->get('/admin/orders')->assertOk()->assertSee($product->title);
 }

 public function test_checkout_persists_chosen_provider(): void
 {
  $this->fakeStripe();$product=$this->product();$customer=User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$product->id,['license_type_id'=>LicenseType::first()->id]);
  $this->post('/checkout',['payment_provider'=>'stripe'])->assertRedirectContains('checkout.stripe.test');
  $this->assertSame('stripe',$customer->orders()->firstOrFail()->payment_provider);
 }

 public function test_paystack_webhook_verifies_signature_and_fulfils_once(): void
 {
  config(['services.paystack.secret'=>'sk_test']);
  Setting::put('payments.paystack.enabled','1','payments');
  $product=$this->product();$customer=User::factory()->create();
  // Build a pending Paystack order directly so we can drive its webhook.
  $order=Order::create(['number'=>'DM-PS-1','user_id'=>$customer->id,'payment_provider'=>'paystack','currency'=>'NGN','subtotal'=>40,'total'=>40,'payment_status'=>'pending','status'=>'awaiting_payment']);
  $order->items()->create(['product_id'=>$product->id,'seller_id'=>$product->seller_id,'license_type_id'=>LicenseType::first()->id,'product_title'=>'Kit','seller_name'=>'Studio','license_name'=>'Regular License','unit_price'=>40,'platform_commission'=>8,'seller_earning'=>32,'total'=>40]);
  $payload=json_encode(['event'=>'charge.success','data'=>['id'=>99,'reference'=>$order->number,'metadata'=>['order_id'=>(string)$order->id]]]);
  $sig=hash_hmac('sha512',$payload,'sk_test');
  $this->call('POST','/payments/paystack/webhook',[],[],[],['HTTP_X_PAYSTACK_SIGNATURE'=>$sig,'CONTENT_TYPE'=>'application/json'],$payload)->assertOk();
  $this->call('POST','/payments/paystack/webhook',[],[],[],['HTTP_X_PAYSTACK_SIGNATURE'=>$sig,'CONTENT_TYPE'=>'application/json'],$payload)->assertOk();
  $this->assertSame('paid',$order->fresh()->payment_status);
  $this->assertDatabaseCount('payments',1);
  $this->assertDatabaseCount('licenses',1);
 }

 public function test_paystack_webhook_rejects_bad_signature(): void
 {
  config(['services.paystack.secret'=>'sk_test']);
  $payload=json_encode(['event'=>'charge.success','data'=>['reference'=>'x','metadata'=>['order_id'=>'1']]]);
  $this->call('POST','/payments/paystack/webhook',[],[],[],['HTTP_X_PAYSTACK_SIGNATURE'=>'wrong','CONTENT_TYPE'=>'application/json'],$payload)->assertServerError();
  $this->assertDatabaseCount('payments',0);
 }

 public function test_bank_transfer_confirmation_by_admin_fulfils_order(): void
 {
  $product=$this->product();$customer=User::factory()->create();
  $admin=User::factory()->create();$admin->roles()->attach(\App\Models\Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $order=Order::create(['number'=>'DM-BT-1','user_id'=>$customer->id,'payment_provider'=>'bank_transfer','currency'=>'USD','subtotal'=>40,'total'=>40,'payment_status'=>'pending','status'=>'awaiting_payment']);
  $order->items()->create(['product_id'=>$product->id,'seller_id'=>$product->seller_id,'license_type_id'=>LicenseType::first()->id,'product_title'=>'Kit','seller_name'=>'Studio','license_name'=>'Regular License','unit_price'=>40,'platform_commission'=>8,'seller_earning'=>32,'total'=>40]);
  $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/confirm-transfer',['reference'=>'WIRE-123'])->assertRedirect();
  $this->assertSame('paid',$order->fresh()->payment_status);
  $this->assertDatabaseCount('licenses',1);
 }
}
