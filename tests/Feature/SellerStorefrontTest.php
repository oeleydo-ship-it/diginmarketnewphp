<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SellerStorefrontTest extends TestCase
{
 use RefreshDatabase;
 private function seller(bool $featured = false): SellerProfile
 {
  $user=User::factory()->create();
  return SellerProfile::create(['user_id'=>$user->id,'display_name'=>'Nova Studio','username'=>'nova-'.$user->id,'country'=>'AE','biography'=>'Great work.','status'=>SellerStatus::Approved,'is_featured'=>$featured]);
 }
 private function product(User $seller, array $attrs = []): Product
 {
  $category=Category::firstOrCreate(['slug'=>'store-apps'],['name'=>'Store Apps']);
  static $n=0;$n++;
  return Product::create(array_merge(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Product '.$n,'slug'=>'product-'.$seller->id.'-'.$n,'short_description'=>'s','description'=>str_repeat('detail ',30),'regular_price'=>'20.00','status'=>ProductStatus::Published,'published_at'=>now()],$attrs));
 }
 public function test_storefront_shows_aggregated_stats(): void
 {
  $profile=$this->seller();$seller=$profile->user;
  $this->product($seller,['sales_count'=>100]);
  $this->product($seller,['sales_count'=>50]);
  // Followers and following.
  $fan=User::factory()->create();$fan->followedSellers()->attach($seller->id);
  $other=$this->seller();$seller->followedSellers()->attach($other->user_id);
  $res=$this->get('/authors/'.$profile->username);
  $res->assertOk()->assertSee('Total sales')->assertSee('150')->assertSee('Followers')->assertSee('Following')->assertSee('Products');
 }
 public function test_storefront_shows_average_rating_from_approved_reviews(): void
 {
  $profile=$this->seller();$seller=$profile->user;
  $product=$this->product($seller);
  $license=LicenseType::firstOrCreate(['slug'=>'regular'],['name'=>'Regular','description'=>'Regular']);
  foreach([5,4] as $i => $rating){
   $buyer=User::factory()->create();
   $order=Order::create(['number'=>'ORD-'.$seller->id.'-'.$i,'user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>'20.00','discount'=>'0','tax'=>'0','fees'=>'0','total'=>'20.00','payment_status'=>'paid','status'=>'completed']);
   $item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$license->id,'product_title'=>$product->title,'seller_name'=>'Nova','license_name'=>'Regular','unit_price'=>'20.00','discount'=>'0','tax'=>'0','platform_commission'=>'2.00','seller_earning'=>'18.00','total'=>'20.00']);
   Review::create(['product_id'=>$product->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'rating'=>$rating,'title'=>'T','content'=>'Good enough review text.','status'=>'approved']);
  }
  $this->get('/authors/'.$profile->username)->assertOk()->assertSee('4.5')->assertSee('2 reviews');
 }
 public function test_featured_author_badge_and_featured_products_section(): void
 {
  $profile=$this->seller(true);$seller=$profile->user;
  $this->product($seller,['title'=>'Regular One']);
  $this->product($seller,['title'=>'Star Product','is_featured'=>true]);
  $this->get('/authors/'.$profile->username)->assertOk()->assertSee('Featured Author')->assertSee('Featured products')->assertSee('Star Product');
 }
 public function test_non_featured_author_hides_badge_and_section(): void
 {
  $profile=$this->seller(false);
  $this->product($profile->user);
  $this->get('/authors/'.$profile->username)->assertOk()->assertDontSee('Featured Author')->assertDontSee('Featured products');
 }
 public function test_admin_can_toggle_featured_status(): void
 {
  $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $profile=$this->seller(false);
  $this->actingAs($admin)->put('/admin/sellers/'.$profile->id.'/feature')->assertRedirect();
  $this->assertTrue($profile->fresh()->is_featured);
  $this->actingAs($admin)->put('/admin/sellers/'.$profile->id.'/feature')->assertRedirect();
  $this->assertFalse($profile->fresh()->is_featured);
 }
 public function test_pending_seller_cannot_be_featured(): void
 {
  $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $user=User::factory()->create();
  $profile=SellerProfile::create(['user_id'=>$user->id,'display_name'=>'Pending','username'=>'pending-x','country'=>'AE','biography'=>'x','status'=>SellerStatus::Pending]);
  $this->actingAs($admin)->put('/admin/sellers/'.$profile->id.'/feature')->assertStatus(422);
    }

    public function test_customer_can_follow_seller_from_storefront_form(): void
    {
        $profile = $this->seller();
        $customer = User::factory()->create();

        // Storefront renders the follow form that posts to the follow toggle endpoint.
        $this->actingAs($customer)
            ->get('/authors/' . $profile->username)
            ->assertOk()
            ->assertSee('/authors/' . $profile->id . '/follow');

        // POSTing Follow should insert the pivot row and redirect back to the storefront.
        $this->actingAs($customer)
            ->post('/authors/' . $profile->id . '/follow')
            ->assertRedirect('/authors/' . $profile->username);

        $this->assertDatabaseHas('seller_followers', [
            'seller_id' => $profile->user_id,
            'follower_id' => $customer->id,
        ]);
    }
}
