<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\DirectCheckoutService;
use App\Services\Gateways\StripeCheckoutGateway;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminReportsAndSelfPurchaseTest extends TestCase
{
 use RefreshDatabase;
 private User $seller;

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
    return ['id' => 'cs_r_'.$order->id, 'url' => 'https://checkout.stripe.test/'.$order->id];
   }
  });
  $this->seller = User::factory()->create();
  $this->seller->roles()->attach(Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']));
  SellerProfile::create(['user_id' => $this->seller->id, 'display_name' => 'Studio', 'username' => 'studio-'.$this->seller->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  LicenseType::firstOrCreate(['slug' => 'regular'], ['name' => 'Regular License', 'description' => 'r']);
 }

 private function product(string $title = 'Kit', string $price = '40.00'): Product
 {
  $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps']);
  return Product::create(['seller_id' => $this->seller->id, 'category_id' => $category->id, 'title' => $title, 'slug' => str($title)->slug().'-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => $price, 'status' => ProductStatus::Published, 'published_at' => now()]);
 }

 private function admin(): User
 {
  $admin = User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  return $admin;
 }

 public function test_reports_page_shows_revenue_commission_and_top_lists(): void
 {
  $product = $this->product('Analytics Kit', '100.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id]);
  $this->post('/checkout', ['payment_provider' => 'stripe']);
  app(PaymentFulfillmentService::class)->fulfill($buyer->orders()->firstOrFail()->id, 'pi_report');
  $response = $this->actingAs($this->admin())->get('/admin/reports');
  $response->assertOk()
   ->assertSee('Sales reports')
   ->assertSee('$100.00')          // gross revenue card
   ->assertSee('$20.00')           // 20% default platform commission
   ->assertSee('Analytics Kit')    // top products
   ->assertSee('Studio');          // top sellers
 }

 public function test_reports_respect_date_range_filter(): void
 {
  $product = $this->product('Old Sale', '100.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id]);
  $this->post('/checkout', ['payment_provider' => 'stripe']);
  $order = $buyer->orders()->firstOrFail();
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_old');
  $order->update(['paid_at' => now()->subDays(90)]);
  // A window that excludes the sale reports zero revenue for it.
  $this->actingAs($this->admin())->get('/admin/reports?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString())
   ->assertOk()->assertDontSee('Old Sale');
 }

 public function test_reports_export_streams_line_items(): void
 {
  $product = $this->product('CSV Kit', '55.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id]);
  $this->post('/checkout', ['payment_provider' => 'stripe']);
  app(PaymentFulfillmentService::class)->fulfill($buyer->orders()->firstOrFail()->id, 'pi_csv');
  $response = $this->actingAs($this->admin())->get('/admin/reports/export');
  $response->assertOk();
  $this->assertStringContainsString('CSV Kit', $response->streamedContent());
 }

 public function test_reports_are_admin_only(): void
 {
  $this->actingAs(User::factory()->create())->get('/admin/reports')->assertForbidden();
 }

 public function test_seller_cannot_add_own_product_to_cart(): void
 {
  $product = $this->product();
  $this->actingAs($this->seller)->post('/cart/'.$product->id, ['license_type_id' => LicenseType::first()->id])->assertStatus(422);
  $this->assertDatabaseCount('cart_items', 0);
 }

 public function test_checkout_rejects_cart_containing_own_product(): void
 {
  // Simulate an item that slipped into the cart before the guard (e.g. user became the seller later).
  $product = $this->product();
  $cart = $this->seller->cart()->create(['currency' => 'USD']);
  $cart->items()->create(['product_id' => $product->id, 'license_type_id' => LicenseType::first()->id, 'unit_price' => 40, 'tax' => 0, 'discount' => 0, 'total' => 40]);
  $this->actingAs($this->seller)->post('/checkout', ['payment_provider' => 'stripe'])->assertStatus(422);
  $this->assertDatabaseCount('orders', 0);
 }

 public function test_seller_cannot_buy_own_bundle(): void
 {
  $bundle = Bundle::create(['seller_id' => $this->seller->id, 'title' => 'Own Pack', 'slug' => 'own-pack', 'price' => '30.00', 'is_active' => true]);
  $bundle->products()->sync([$this->product('A')->id, $this->product('B')->id]);
  $this->actingAs($this->seller);
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
  app(DirectCheckoutService::class)->startBundle($bundle, $this->seller);
 }

 public function test_own_product_page_shows_manage_link_instead_of_buy(): void
 {
  $product = $this->product();
  $this->actingAs($this->seller)->get('/products/'.$product->slug)->assertOk()->assertSee('This is your product')->assertDontSee('Add to Cart');
  $this->actingAs(User::factory()->create())->get('/products/'.$product->slug)->assertOk()->assertSee('Add to Cart');
 }
}
