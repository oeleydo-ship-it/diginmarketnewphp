<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class MultiGatewayRefundTest extends TestCase
{
 use RefreshDatabase;

 /** Paid order + item + license + payment for the given provider, with a refund request. */
 private function refundRequestFor(string $provider, string $paymentId): RefundRequest
 {
  $seller = User::factory()->create();
  $buyer = User::factory()->create();
  $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps']);
  $type = LicenseType::firstOrCreate(['slug' => 'regular'], ['name' => 'Regular', 'description' => 'r']);
  $product = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Refundable', 'slug' => 'refundable-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 100, 'status' => ProductStatus::Published]);
  $order = Order::create(['number' => 'DM-RFND-'.uniqid(), 'user_id' => $buyer->id, 'currency' => 'USD', 'subtotal' => 100, 'total' => 100, 'payment_status' => 'paid', 'status' => 'completed', 'payment_provider' => $provider, 'paid_at' => now()]);
  $item = $order->items()->create(['product_id' => $product->id, 'seller_id' => $seller->id, 'license_type_id' => $type->id, 'product_title' => 'Refundable', 'seller_name' => $seller->name, 'license_name' => 'Regular', 'unit_price' => 100, 'platform_commission' => 20, 'seller_earning' => 80, 'total' => 100]);
  $order->payments()->create(['provider' => $provider, 'provider_payment_id' => $paymentId, 'amount' => 100, 'currency' => 'USD', 'status' => 'succeeded', 'paid_at' => now()]);
  \App\Models\SellerWallet::create(['seller_id' => $seller->id, 'currency' => 'USD', 'pending_balance' => 80, 'lifetime_earnings' => 80]);
  License::create(['license_key' => 'DM-'.uniqid(), 'product_id' => $product->id, 'user_id' => $buyer->id, 'order_item_id' => $item->id, 'license_type_id' => $type->id, 'status' => 'active']);
  return RefundRequest::create(['number' => 'RF-'.strtoupper(uniqid()), 'user_id' => $buyer->id, 'order_id' => $order->id, 'order_item_id' => $item->id, 'product_id' => $product->id, 'seller_id' => $seller->id, 'reason' => 'test', 'description' => str_repeat('why ', 10), 'requested_amount' => 100, 'status' => 'submitted']);
 }

 private function admin(): User
 {
  return User::factory()->create();
 }

 public function test_paypal_order_refunds_through_paypal_api(): void
 {
  config(['services.paypal.client_id' => 'id', 'services.paypal.secret' => 'sec']);
  Http::fake([
   '*/v1/oauth2/token' => Http::response(['access_token' => 't']),
   '*/v2/payments/captures/CAP-1/refund' => Http::response(['id' => 'REF-1', 'status' => 'COMPLETED']),
  ]);
  $request = $this->refundRequestFor('paypal', 'CAP-1');
  app(RefundService::class)->approve($request, $this->admin(), 100);
  $this->assertSame('refunded', $request->fresh()->status);
  $this->assertDatabaseHas('wallet_transactions', ['type' => 'refund_deduction']);
  Http::assertSent(fn ($req) => str_contains($req->url(), '/v2/payments/captures/CAP-1/refund'));
 }

 public function test_razorpay_order_refunds_through_razorpay_api(): void
 {
  config(['services.razorpay.key' => 'k', 'services.razorpay.secret' => 's']);
  Http::fake(['api.razorpay.com/v1/payments/pay_9/refund' => Http::response(['id' => 'rfnd_1', 'status' => 'processed'])]);
  $request = $this->refundRequestFor('razorpay', 'pay_9');
  app(RefundService::class)->approve($request, $this->admin(), 100);
  $this->assertSame('refunded', $request->fresh()->status);
 }

 public function test_paystack_order_refunds_through_paystack_api(): void
 {
  config(['services.paystack.secret' => 'sk']);
  Http::fake(['api.paystack.co/refund' => Http::response(['status' => true, 'data' => ['id' => 991]])]);
  $request = $this->refundRequestFor('paystack', 'DM-REF-99');
  app(RefundService::class)->approve($request, $this->admin(), 100);
  $this->assertSame('refunded', $request->fresh()->status);
 }

 public function test_bank_transfer_refund_succeeds_without_provider_call(): void
 {
  Http::fake();
  $request = $this->refundRequestFor('bank_transfer', 'bt:manual-1');
  app(RefundService::class)->approve($request, $this->admin(), 100);
  $this->assertSame('refunded', $request->fresh()->status);
  Http::assertNothingSent();
 }

 public function test_failed_provider_refund_leaves_everything_untouched(): void
 {
  config(['services.paypal.client_id' => 'id', 'services.paypal.secret' => 'sec']);
  Http::fake([
   '*/v1/oauth2/token' => Http::response(['access_token' => 't']),
   '*/v2/payments/captures/CAP-BAD/refund' => Http::response(['message' => 'CAPTURE_FULLY_REFUNDED'], 422),
  ]);
  $request = $this->refundRequestFor('paypal', 'CAP-BAD');
  try {
   app(RefundService::class)->approve($request, $this->admin(), 100);
   $this->fail('Expected the refund to fail.');
  } catch (\RuntimeException) {
  }
  $this->assertSame('submitted', $request->fresh()->status);
  $this->assertDatabaseMissing('wallet_transactions', ['type' => 'refund_deduction']);
 }
}
