<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MarketplaceDiscoveryTest extends TestCase
{
 use RefreshDatabase;
 private function catalog(): array
 {
  $category=Category::create(['name'=>'Laravel Apps','slug'=>'laravel-apps','description'=>'Production-ready Laravel software.']);
  $seller=User::factory()->create();SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'North Studio','username'=>'north-studio','country'=>'AE','biography'=>'Verified application studio.','status'=>SellerStatus::Approved]);
  $published=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Laravel CRM','slug'=>'laravel-crm','short_description'=>'Customer relationship management.','description'=>str_repeat('Detailed CRM functionality. ',4),'regular_price'=>'39.00','status'=>ProductStatus::Published,'published_at'=>now(),'sales_count'=>12,'average_rating'=>'4.80']);
  // Second published product: lazy-loading violations only trigger on multi-model
  // collections, so single-product pages would hide missing eager loads.
  $second=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Laravel Helpdesk','slug'=>'laravel-helpdesk','short_description'=>'Support ticketing.','description'=>str_repeat('Detailed helpdesk functionality. ',4),'regular_price'=>'29.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $draft=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Secret Draft','slug'=>'secret-draft','short_description'=>'Not public.','description'=>str_repeat('Private draft. ',5),'regular_price'=>'10.00','status'=>ProductStatus::Draft]);
  return compact('category','seller','published','second','draft');
 }
 public function test_search_and_category_pages_only_show_published_matching_products(): void
 {
  $data=$this->catalog();$this->get('/products?q=CRM&min_price=20&max_price=50&sort=popular')->assertOk()->assertSee('Laravel CRM')->assertDontSee('Secret Draft');$this->get('/categories/laravel-apps')->assertOk()->assertSee('Laravel CRM')->assertDontSee('Secret Draft');$this->get('/products?q=Secret')->assertOk()->assertDontSee('Secret Draft');
 }
 public function test_category_page_filters_by_price_and_search_and_ignores_foreign_category_param(): void
 {
  $data=$this->catalog();
  // Laravel CRM ($39, category laravel-apps) + Laravel Helpdesk ($29). Filter min_price=35 keeps only CRM.
  $this->get('/categories/laravel-apps?min_price=35')->assertOk()->assertSee('Laravel CRM')->assertDontSee('Laravel Helpdesk')->assertSee('match your filters');
  // Search within the category.
  $this->get('/categories/laravel-apps?q=Helpdesk')->assertOk()->assertSee('Laravel Helpdesk')->assertDontSee('Laravel CRM');
  // A ?category= param must not override the page's own category.
  $other=Category::create(['name'=>'Other','slug'=>'other-cat']);
  Product::create(['seller_id'=>$data['seller']->id,'category_id'=>$other->id,'title'=>'Outsider Tool','slug'=>'outsider-tool','short_description'=>'x','description'=>str_repeat('y ',30),'regular_price'=>'20.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $this->get('/categories/laravel-apps?category=other-cat')->assertOk()->assertSee('Laravel CRM')->assertDontSee('Outsider Tool');
 }
 public function test_category_page_supports_grid_and_list_views(): void
 {
  $data=$this->catalog();
  // List is the default view: product-row layout renders.
  $this->get('/categories/laravel-apps')->assertOk()->assertSee('Laravel CRM')->assertSee('sm:h-36 sm:w-52',false);
  // Grid view switches to product-card thumbnails (no list rows).
  $this->get('/categories/laravel-apps?view=grid')->assertOk()->assertSee('Laravel CRM')->assertDontSee('sm:h-36 sm:w-52',false);
 }
 public function test_approved_seller_has_public_storefront(): void
 {
  $data=$this->catalog();$this->get('/authors/north-studio')->assertOk()->assertSee('North Studio')->assertSee('Laravel CRM')->assertDontSee('Secret Draft');
 }
 public function test_customer_can_wishlist_published_product_and_follow_seller(): void
 {
  $data=$this->catalog();$customer=User::factory()->create();$this->actingAs($customer)->post('/wishlist/'.$data['published']->id)->assertRedirect();$this->post('/wishlist/'.$data['second']->id)->assertRedirect();$this->assertDatabaseHas('wishlist_items',['product_id'=>$data['published']->id]);$this->get('/wishlist')->assertOk()->assertSee('Laravel CRM')->assertSee('Laravel Helpdesk');$profile=$data['seller']->sellerProfile;$this->post('/authors/'.$profile->id.'/follow')->assertRedirect();$this->assertDatabaseHas('seller_followers',['seller_id'=>$data['seller']->id,'follower_id'=>$customer->id]);
 }
 public function test_sitemap_excludes_unpublished_products(): void
 {
    $this->catalog();

    $response = $this->get('/sitemap.xml')
      ->assertOk()
      ->assertHeader('Content-Type', 'application/xml')
      ->assertSee('laravel-crm')
      ->assertDontSee('secret-draft');

    // SitemapController now outputs absolute URLs.
    $response->assertSee(url('/'));
    $response->assertSee(url('/products/laravel-crm'));
 }
 public function test_product_views_are_counted_once_per_session(): void
 {
  $data=$this->catalog();$this->get('/products/laravel-crm')->assertOk();$this->get('/products/laravel-crm')->assertOk();$this->assertSame(1,$data['published']->fresh()->views_count);
 }
 public function test_search_suggests_published_products_only(): void
 {
  $this->catalog();
  $this->getJson('/products/suggest?q=CRM')->assertOk()->assertJsonFragment(['title'=>'Laravel CRM'])->assertJsonMissing(['title'=>'Secret Draft']);
  $this->getJson('/products/suggest?q=Secret')->assertOk()->assertJsonPath('products', []);
  $this->getJson('/products/suggest?q=x')->assertOk()->assertJsonPath('products', []);
 }
 public function test_recently_viewed_products_appear_on_the_homepage(): void
 {
  $this->catalog();
  $this->get('/products/laravel-crm')->assertOk();
  $this->get('/')->assertOk()->assertSee('Recently viewed')->assertSee('Laravel CRM');
 }
 public function test_guest_can_add_published_product_to_cart_with_default_license_after_login(): void
 {
  $data=$this->catalog();
  \App\Models\LicenseType::create(['name'=>'Regular License','slug'=>'regular','description'=>'Regular']);
  $customer=\App\Models\User::factory()->create();
  $this->actingAs($customer)->post('/cart/'.$data['published']->id)->assertRedirect('/cart');
  $this->get('/cart')->assertOk()->assertSee('Laravel CRM');
 }
}