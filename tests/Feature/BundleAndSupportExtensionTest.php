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

 public function test_product_page_offers_six_month_addon_when_seller_sets_a_price(): void
 {
  $product = $this->product('Alpha', '60.00', ['support_extension_price' => '18.00']);
  $this->get('/products/'.$product->slug)
   ->assertOk()
   ->assertSee('6 months extra updates & support')
   ->assertSee('$18.00')
   ->assertSee('Sign in and buy the product first to purchase this addon')
   ->assertDontSee('Buy 6-month addon');
 }

 public function test_product_page_hides_addon_when_extension_price_is_null(): void
 {
  $product = $this->product('Alpha', '60.00');
  $this->get('/products/'.$product->slug)
   ->assertOk()
   ->assertDontSee('extra updates &amp; support', false)
   ->assertDontSee('Buy 6-month addon');
 }

 public function test_license_owner_can_buy_addon_from_product_purchases_and_downloads(): void
 {
  $product = $this->product('Alpha', '60.00', ['support_extension_price' => '18.00', 'support_extension_months' => 6]);
  $buyer = User::factory()->create();
  $license = $this->licenseFor($product, $buyer, ['support_expires_at' => now()->addMonth()]);
  $this->actingAs($buyer);
  $this->get('/products/'.$product->slug)->assertOk()->assertSee('Buy 6-month addon')->assertSee('Additional payment on your existing license');
  $this->get(route('purchases.show', $license->orderItem->order))->assertOk()->assertSee('Buy 6-month addon');
  $this->get(route('downloads.index'))->assertOk()->assertSee('Buy 6-month addon');
  $this->post(route('licenses.extend-support', $license))->assertRedirectContains('checkout.stripe.test');
  $order = $buyer->orders()->latest('id')->first();
  $this->assertEquals(18.00, (float) $order->total);
  $this->assertSame('support_extension', $order->items->first()->item_type);
  $this->assertSame($license->id, $order->items->first()->license_id);
 }

 public function test_support_addon_defaults_to_six_months_and_stacks(): void
 {
  $product = $this->product('Alpha', '60.00', ['support_extension_price' => '12.00', 'support_extension_months' => 0]);
  $buyer = User::factory()->create();
  $license = $this->licenseFor($product, $buyer, ['support_expires_at' => now()->addMonth()]);
  $originalExpiry = $license->support_expires_at->copy();
  $this->actingAs($buyer);
  $first = app(DirectCheckoutService::class)->startSupportExtension($license, $buyer);
  $this->assertStringContainsString('+6 months of updates & support', $first['order']->items->first()->license_name);
  app(PaymentFulfillmentService::class)->fulfill($first['order']->id, 'pi_support_1');
  $second = app(DirectCheckoutService::class)->startSupportExtension($license->fresh(), $buyer);
  app(PaymentFulfillmentService::class)->fulfill($second['order']->id, 'pi_support_2');
  $this->assertTrue($license->fresh()->support_expires_at->equalTo($originalExpiry->addMonths(12)));
  $this->assertSame(1, License::count());
 }

 public function test_bundles_index_lists_purchasable_bundles_and_hides_broken_ones(): void
 {
  // Two purchasable bundles (≥2 models so the lazy-load guard is armed) and one with a draft product.
  $bundleA = $this->bundle([$this->product('Alpha', '60.00'), $this->product('Beta', '40.00')], '50.00');
  $bundleA->update(['title' => 'Complete Toolkit']);
  $bundleB = $this->bundle([$this->product('Gamma', '30.00'), $this->product('Delta', '30.00')], '45.00');
  $bundleB->update(['title' => 'Design Duo']);
  $broken = $this->bundle([$this->product('Live', '30.00'), $this->product('Dead', '30.00', ['status' => ProductStatus::Draft])], '20.00');
  $broken->update(['title' => 'Broken Pack']);
  $response = $this->get('/bundles');
  $response->assertOk()->assertSee('Complete Toolkit')->assertSee('Design Duo')->assertDontSee('Broken Pack');
  // 50 vs 100 compare-at → "Save 50%".
  $response->assertSee('Save 50%');
 }

 public function test_bundles_index_search_filters_by_title(): void
 {
  $this->bundle([$this->product('A1', '10.00'), $this->product('A2', '10.00')], '15.00')->update(['title' => 'Winter Pack']);
  $this->bundle([$this->product('B1', '10.00'), $this->product('B2', '10.00')], '15.00')->update(['title' => 'Summer Pack']);
  $this->get('/bundles?q=Winter')->assertOk()->assertSee('Winter Pack')->assertDontSee('Summer Pack');
 }

 public function test_product_page_cross_sells_its_bundles(): void
 {
  $product = $this->product('Alpha', '60.00');
  $bundle = $this->bundle([$product, $this->product('Beta', '40.00')], '50.00');
  $bundle->update(['title' => 'Alpha Mega Deal']);
  $this->get('/products/'.$product->slug)->assertOk()->assertSee('Save with a bundle')->assertSee('Alpha Mega Deal');
  // A product in no bundles shows no cross-sell block.
  $solo = $this->product('Solo', '20.00');
  $this->get('/products/'.$solo->slug)->assertOk()->assertDontSee('Save with a bundle');
 }

 public function test_storefront_shows_seller_bundles(): void
 {
  $bundle = $this->bundle([$this->product('Alpha', '60.00'), $this->product('Beta', '40.00')], '50.00');
  $bundle->update(['title' => 'Studio Collection']);
  $username = $this->seller->sellerProfile->username;
  $this->get('/authors/'.$username)->assertOk()->assertSee('Studio Collection');
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
