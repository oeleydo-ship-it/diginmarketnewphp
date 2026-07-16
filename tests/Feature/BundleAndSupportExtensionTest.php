<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\License;
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
class BundleAndSupportExtensionTest extends TestCase
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
    return ['id' => 'cs_direct_'.$order->id, 'url' => 'https://checkout.stripe.test/'.$order->id];
   }
  });
  $this->seller = User::factory()->create();
  $this->seller->roles()->attach(Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']));
  SellerProfile::create(['user_id' => $this->seller->id, 'display_name' => 'Studio', 'username' => 'studio-'.$this->seller->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  LicenseType::firstOrCreate(['slug' => 'regular'], ['name' => 'Regular License', 'description' => 'r']);
 }

 private function product(string $title, string $price, array $extra = []): Product
 {
  $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps']);
  return Product::create(array_merge(['seller_id' => $this->seller->id, 'category_id' => $category->id, 'title' => $title, 'slug' => str($title)->slug().'-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => $price, 'status' => ProductStatus::Published, 'published_at' => now()], $extra));
 }

 private function bundle(array $products, string $price = '50.00'): Bundle
 {
  $bundle = Bundle::create(['seller_id' => $this->seller->id, 'title' => 'Mega Pack', 'slug' => 'mega-pack-'.uniqid(), 'price' => $price, 'is_active' => true]);
  $bundle->products()->sync(collect($products)->pluck('id'));
  return $bundle;
 }

 public function test_bundle_purchase_allocates_price_and_licenses_every_product(): void
 {
  $a = $this->product('Alpha', '60.00');
  $b = $this->product('Beta', '40.00');
  $bundle = $this->bundle([$a, $b], '50.00');
  $buyer = User::factory()->create();
  $this->actingAs($buyer);
  $result = app(DirectCheckoutService::class)->startBundle($bundle, $buyer);
  $order = $result['order'];
  // 50.00 split 60/40 → 30.00 + 20.00, exact to the cent.
  $this->assertEqualsCanonicalizing([30.00, 20.00], $order->items->pluck('total')->map(fn ($t) => (float) $t)->all());
  $this->assertEquals(50.00, (float) $order->total);
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_bundle');
  $this->assertDatabaseCount('licenses', 2);
  $this->assertSame(1, $a->fresh()->sales_count);
  $this->assertSame(1, $b->fresh()->sales_count);
 }

 public function test_bundle_with_unpublished_product_is_not_purchasable(): void
 {
  $a = $this->product('Alpha', '60.00');
  $b = $this->product('Beta', '40.00', ['status' => ProductStatus::Draft]);
  $bundle = $this->bundle([$a, $b]);
  $buyer = User::factory()->create();
  $this->actingAs($buyer);
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
  app(DirectCheckoutService::class)->startBundle($bundle, $buyer);
 }

 /** A license always originates from a paid order item; build that chain for fixtures. */
 private function licenseFor(Product $product, User $buyer, array $extra = []): License
 {
  $order = Order::create(['number' => 'DM-FIX-'.uniqid(), 'user_id' => $buyer->id, 'currency' => 'USD', 'subtotal' => $product->regular_price, 'total' => $product->regular_price, 'payment_status' => 'paid', 'status' => 'completed']);
  $item = $order->items()->create(['product_id' => $product->id, 'seller_id' => $product->seller_id, 'license_type_id' => LicenseType::first()->id, 'product_title' => $product->title, 'seller_name' => 'Studio', 'license_name' => 'Regular License', 'unit_price' => $product->regular_price, 'total' => $product->regular_price]);
  return License::create(array_merge(['license_key' => 'DM-'.uniqid(), 'order_item_id' => $item->id, 'product_id' => $product->id, 'user_id' => $buyer->id, 'license_type_id' => LicenseType::first()->id, 'status' => 'active', 'activation_limit' => 1], $extra));
 }

 public function test_support_extension_extends_license_without_new_license(): void
 {
  $product = $this->product('Alpha', '60.00', ['support_extension_price' => '18.00', 'support_extension_months' => 6]);
  $buyer = User::factory()->create();
  $license = $this->licenseFor($product, $buyer, ['support_expires_at' => now()->addMonth()]);
  $originalExpiry = $license->support_expires_at;
  $this->actingAs($buyer);
  $result = app(DirectCheckoutService::class)->startSupportExtension($license, $buyer);
  app(PaymentFulfillmentService::class)->fulfill($result['order']->id, 'pi_support');
  $license->refresh();
  $this->assertTrue($license->support_expires_at->equalTo($originalExpiry->copy()->addMonths(6)));
  // No second license minted (fixture created exactly one), seller credited, sales count untouched.
  $this->assertSame(1, License::count());
  $this->assertSame(0, $product->fresh()->sales_count);
  $this->assertDatabaseHas('wallet_transactions', ['type' => 'sale_credit']);
 }

 public function test_support_extension_requires_product_to_offer_it(): void
 {
  $product = $this->product('Alpha', '60.00');
  $buyer = User::factory()->create();
  $license = $this->licenseFor($product, $buyer);
  $this->actingAs($buyer);
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
  app(DirectCheckoutService::class)->startSupportExtension($license, $buyer);
 }

 public function test_seller_creates_bundle_via_http_and_public_page_renders(): void
 {
  $a = $this->product('Alpha', '60.00');
  $b = $this->product('Beta', '40.00');
  $this->actingAs($this->seller)->post(route('seller.bundles.store'), ['title' => 'Combo', 'price' => '75.00', 'product_ids' => [$a->id, $b->id]])->assertRedirect();
  $bundle = Bundle::firstOrFail();
  $this->get(route('bundles.show', $bundle->slug))->assertOk()->assertSee('Combo')->assertSee('Alpha')->assertSee('Beta');
 }

 public function test_bundle_cannot_contain_other_sellers_products(): void
 {
  $a = $this->product('Alpha', '60.00');
  $other = User::factory()->create();
  $foreign = Product::create(['seller_id' => $other->id, 'category_id' => $a->category_id, 'title' => 'Foreign', 'slug' => 'foreign-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 10, 'status' => ProductStatus::Published]);
  $this->actingAs($this->seller)->post(route('seller.bundles.store'), ['title' => 'Bad', 'price' => '20.00', 'product_ids' => [$a->id, $foreign->id]])->assertStatus(422);
 }
}
