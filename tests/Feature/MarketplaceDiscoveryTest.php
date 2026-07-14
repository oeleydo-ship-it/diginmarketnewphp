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
  $this->catalog();$this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type','application/xml')->assertSee('laravel-crm')->assertDontSee('secret-draft');
 }
 public function test_product_views_are_counted_once_per_session(): void
 {
  $data=$this->catalog();$this->get('/products/laravel-crm')->assertOk();$this->get('/products/laravel-crm')->assertOk();$this->assertSame(1,$data['published']->fresh()->views_count);
 }
}