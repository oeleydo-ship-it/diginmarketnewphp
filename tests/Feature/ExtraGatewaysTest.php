<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class ExtraGatewaysTest extends TestCase
{
 use RefreshDatabase;

 private function pendingOrder(string $provider, string $currency = 'USD', ?string $checkoutId = null): Order
 {
  $seller = User::factory()->create();
  SellerProfile::create(['user_id' => $seller->id, 'display_name' => 'GW Studio', 'username' => 'gw-'.$seller->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps']);
  $type = LicenseType::firstOrCreate(['slug' => 'regular'], ['name' => 'Regular', 'description' => 'r']);
  $product = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'GW Kit', 'slug' => 'gw-kit-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 50, 'status' => ProductStatus::Published]);
  $order = Order::create(['number' => 'DM-GW-'.strtoupper(uniqid()), 'user_id' => User::factory()->create()->id, 'currency' => $currency, 'subtotal' => 50, 'total' => 50, 'payment_status' => 'pending', 'status' => 'awaiting_payment', 'payment_provider' => $provider, 'provider_checkout_id' => $checkoutId]);
  $order->items()->create(['product_id' => $product->id, 'seller_id' => $seller->id, 'license_type_id' => $type->id, 'product_title' => 'GW Kit', 'seller_name' => 'GW Studio', 'license_name' => 'Regular', 'unit_price' => 50, 'platform_commission' => 10, 'seller_earning' => 40, 'total' => 50]);
  return $order;
 }

 public function test_mollie_checkout_webhook_and_refund_roundtrip(): void
 {
  config(['services.mollie.api_key' => 'test_key']);
  Setting::put('payments.mollie.enabled', '1', 'payments');
  $order = $this->pendingOrder('mollie', 'EUR');
  // One fake for the whole roundtrip: earlier-registered stubs win, so never re-fake a URL.
  Http::fake([
   'api.mollie.com/v2/payments/tr_777/refunds' => Http::response(['id' => 're_1', 'status' => 'pending']),
   'api.mollie.com/v2/payments/tr_777' => Http::response(['id' => 'tr_777', 'status' => 'paid', 'metadata' => ['order_id' => (string) $order->id]]),
   'api.mollie.com/v2/payments' => Http::response(['id' => 'tr_777', '_links' => ['checkout' => ['href' => 'https://pay.mollie.test/777']]]),
  ]);
  $gateway = app(PaymentGatewayManager::class)->driver('mollie');
  $session = $gateway->createCheckout($order);
  $this->assertSame('tr_777', $session['id']);
  $order->update(['provider_checkout_id' => 'tr_777']);
  $this->call('POST', '/payments/mollie/webhook', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], 'id=tr_777')->assertOk();
  $this->assertSame('paid', $order->fresh()->payment_status);
  $this->assertDatabaseCount('licenses', 1);
  // Replay changes nothing.
  $this->call('POST', '/payments/mollie/webhook', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], 'id=tr_777')->assertOk();
  $this->assertDatabaseCount('payments', 1);
 }

 public function test_flutterwave_webhook_requires_hash_and_fulfils(): void
 {
  config(['services.flutterwave.secret' => 'sk', 'services.flutterwave.secret_hash' => 'my-hash']);
  Setting::put('payments.flutterwave.enabled', '1', 'payments');
  $order = $this->pendingOrder('flutterwave', 'NGN');
  $payload = json_encode(['event' => 'charge.completed', 'data' => ['id' => 445566, 'status' => 'successful', 'tx_ref' => $order->number, 'meta' => ['order_id' => (string) $order->id]]]);
  // Wrong hash → rejected, nothing fulfilled.
  try {
   $this->withoutExceptionHandling()->call('POST', '/payments/flutterwave/webhook', [], [], [], ['HTTP_VERIF_HASH' => 'wrong', 'CONTENT_TYPE' => 'application/json'], $payload);
   $this->fail('Expected signature failure');
  } catch (\RuntimeException) {
  }
  $this->assertSame('pending', $order->fresh()->payment_status);
  // Correct hash → paid.
  $this->call('POST', '/payments/flutterwave/webhook', [], [], [], ['HTTP_VERIF_HASH' => 'my-hash', 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
  $this->assertSame('paid', $order->fresh()->payment_status);
 }

 public function test_instamojo_webhook_hmac_and_order_resolution(): void
 {
  config(['services.instamojo.api_key' => 'k', 'services.instamojo.auth_token' => 't', 'services.instamojo.salt' => 'salty']);
  Setting::put('payments.instamojo.enabled', '1', 'payments');
  $order = $this->pendingOrder('instamojo', 'INR', 'PRQ-123');
  $fields = ['payment_id' => 'MOJO-9', 'payment_request_id' => 'PRQ-123', 'status' => 'Credit', 'amount' => '50.00'];
  ksort($fields);
  $mac = hash_hmac('sha1', implode('|', $fields), 'salty');
  $payload = http_build_query($fields + ['mac' => $mac]);
  $this->call('POST', '/payments/instamojo/webhook', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], $payload)->assertOk();
  $this->assertSame('paid', $order->fresh()->payment_status);
  $this->assertDatabaseHas('payments', ['provider' => 'instamojo', 'provider_payment_id' => 'MOJO-9']);
 }

 public function test_sslcommerz_ipn_validates_against_api_before_fulfilling(): void
 {
  config(['services.sslcommerz.store_id' => 'store1', 'services.sslcommerz.store_password' => 'pass1']);
  Setting::put('payments.sslcommerz.enabled', '1', 'payments');
  $order = $this->pendingOrder('sslcommerz', 'BDT');
  Http::fake(['sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response(['status' => 'VALID', 'tran_id' => $order->number])]);
  $this->call('POST', '/payments/sslcommerz/webhook', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], 'val_id=VAL-1&status=VALID')->assertOk();
  $this->assertSame('paid', $order->fresh()->payment_status);
  // Invalid validations never fulfil.
  $other = $this->pendingOrder('sslcommerz', 'BDT');
  Http::fake(['sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response(['status' => 'INVALID_TRANSACTION', 'tran_id' => $other->number])]);
  $this->call('POST', '/payments/sslcommerz/webhook', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], 'val_id=VAL-2')->assertOk();
  $this->assertSame('pending', $other->fresh()->payment_status);
 }

 public function test_new_gateways_appear_in_checkout_when_enabled_and_currency_matches(): void
 {
  config(['services.mollie.api_key' => 'k', 'services.instamojo.api_key' => 'k', 'services.instamojo.auth_token' => 't']);
  Setting::put('payments.mollie.enabled', '1', 'payments');
  Setting::put('payments.instamojo.enabled', '1', 'payments');
  $manager = app(PaymentGatewayManager::class);
  $this->assertContains('mollie', $manager->availableKeysFor('EUR'));
  // Instamojo is INR-only, so a EUR cart never offers it.
  $this->assertNotContains('instamojo', $manager->availableKeysFor('EUR'));
  $this->assertContains('instamojo', $manager->availableKeysFor('INR'));
 }
}
