<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Gateways\StripeCheckoutGateway;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TaxAndInvoiceTest extends TestCase
{
 use RefreshDatabase;

 protected function setUp(): void
 {
  parent::setUp();
  $this->app->bind(StripeCheckoutGateway::class, fn () => new class extends StripeCheckoutGateway
  {
   public function isConfigured(): bool
   {
    return true;
   }

   public function createCheckout(Order $order): array
   {
    return ['id' => 'cs_tax_'.$order->id, 'url' => 'https://checkout.stripe.test/'.$order->id];
   }
  });
 }

 private function product(string $price = '100.00'): Product
 {
  $seller = User::factory()->create();
  SellerProfile::create(['user_id' => $seller->id, 'display_name' => 'Tax Studio', 'username' => 'tax-studio-'.$seller->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps']);
  LicenseType::firstOrCreate(['slug' => 'regular'], ['name' => 'Regular License', 'description' => 'r']);
  return Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Taxable Kit', 'slug' => 'taxable-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => $price, 'status' => ProductStatus::Published, 'published_at' => now()]);
 }

 public function test_tax_is_added_on_top_and_commission_stays_net(): void
 {
  config(['marketplace.tax_rate' => 10.0, 'marketplace.tax_label' => 'VAT']);
  $product = $this->product('100.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id]);
  $this->get('/cart')->assertOk()->assertSee('VAT')->assertSee('110.00');
  $this->post('/checkout', ['payment_provider' => 'stripe']);
  $order = $buyer->orders()->with('items')->firstOrFail();
  $this->assertEquals(100.00, (float) $order->subtotal);
  $this->assertEquals(10.00, (float) $order->tax);
  $this->assertEquals(110.00, (float) $order->total);
  $item = $order->items->first();
  // Item total stays net; the 20% default commission applies to 100, never 110.
  $this->assertEquals(100.00, (float) $item->total);
  $this->assertEquals(10.00, (float) $item->tax);
  $this->assertEquals(20.00, (float) $item->platform_commission);
  $this->assertEquals(80.00, (float) $item->seller_earning);
 }

 public function test_zero_rate_changes_nothing(): void
 {
  config(['marketplace.tax_rate' => 0]);
  $product = $this->product('40.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id]);
  $this->post('/checkout', ['payment_provider' => 'stripe']);
  $order = $buyer->orders()->firstOrFail();
  $this->assertEquals(0.0, (float) $order->tax);
  $this->assertEquals(40.00, (float) $order->total);
 }

 public function test_invoice_renders_for_paid_orders_and_is_owner_only(): void
 {
  config(['marketplace.tax_rate' => 5.0, 'marketplace.tax_label' => 'GST']);
  $product = $this->product('60.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id]);
  $this->post('/checkout', ['payment_provider' => 'stripe']);
  $order = $buyer->orders()->firstOrFail();
  // Unpaid: no invoice yet.
  $this->get('/purchases/'.$order->id.'/invoice')->assertNotFound();
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_invoice');
  $this->get('/purchases/'.$order->id.'/invoice')->assertOk()
   ->assertSee($order->number)->assertSee('Taxable Kit')->assertSee('GST')->assertSee('63.00');
  // The purchase page links to it; strangers cannot open it.
  $this->get('/purchases/'.$order->id)->assertOk()->assertSee('Invoice');
  $this->actingAs(User::factory()->create())->get('/purchases/'.$order->id.'/invoice')->assertForbidden();
 }
}
