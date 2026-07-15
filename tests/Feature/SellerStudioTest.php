<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SellerStudioTest extends TestCase
{
 use RefreshDatabase;
 private function seller(): User
 {
  $user=User::factory()->create();
  $user->roles()->attach(Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']));
  SellerProfile::create(['user_id'=>$user->id,'display_name'=>'Studio','username'=>'studio-'.$user->id,'country'=>'AE','biography'=>'x','status'=>SellerStatus::Approved]);
  return $user;
 }
 private function sale(User $seller, User $buyer, Product $product, string $earning = '18.00'): void
 {
  $license=LicenseType::firstOrCreate(['slug'=>'regular'],['name'=>'Regular','description'=>'r']);
  static $n=0;$n++;
  $order=Order::create(['number'=>'O-'.$seller->id.'-'.$n,'user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>'20.00','discount'=>'0','tax'=>'0','fees'=>'0','total'=>'20.00','payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);
  $order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$license->id,'product_title'=>$product->title,'seller_name'=>'Studio','license_name'=>'Regular','unit_price'=>'20.00','discount'=>'0','tax'=>'0','platform_commission'=>'2.00','seller_earning'=>$earning,'total'=>'20.00']);
 }
 private function product(User $seller, array $a = []): Product
 {
  $cat=Category::firstOrCreate(['slug'=>'studio-cat'],['name'=>'Studio Cat']);
  static $n=0;$n++;
  return Product::create(array_merge(['seller_id'=>$seller->id,'category_id'=>$cat->id,'title'=>'Prod '.$n,'slug'=>'prod-'.$seller->id.'-'.$n,'short_description'=>'s','description'=>str_repeat('d ',30),'regular_price'=>'20.00','status'=>ProductStatus::Published,'published_at'=>now()],$a));
 }
 public function test_dashboard_shows_earnings_and_sales_metrics(): void
 {
  $seller=$this->seller();$p=$this->product($seller,['sales_count'=>3]);
  $b1=User::factory()->create();$b2=User::factory()->create();
  $this->sale($seller,$b1,$p);$this->sale($seller,$b2,$p);
  $this->actingAs($seller)->get('/seller')->assertOk()->assertSee('Overview')->assertSee('Gross revenue')->assertSee('Recent sales');
 }
 public function test_sales_page_lists_only_this_sellers_paid_items(): void
 {
  $seller=$this->seller();$other=$this->seller();
  $mine=$this->product($seller);$theirs=$this->product($other);
  $buyer=User::factory()->create();
  $this->sale($seller,$buyer,$mine);$this->sale($other,$buyer,$theirs,'99.00');
  $this->actingAs($seller)->get('/seller/sales')->assertOk()->assertSee($mine->title)->assertDontSee($theirs->title)->assertSee('Your earnings');
 }
 public function test_customers_page_aggregates_unique_buyers(): void
 {
  $seller=$this->seller();$p=$this->product($seller);
  $buyer=User::factory()->create(['name'=>'Repeat Rita']);
  $this->sale($seller,$buyer,$p);$this->sale($seller,$buyer,$p);
  $lone=User::factory()->create(['name'=>'One Ollie']);$this->sale($seller,$lone,$p);
  $res=$this->actingAs($seller)->get('/seller/customers')->assertOk();
  $res->assertSee('Repeat Rita')->assertSee('One Ollie')->assertSee('Unique customers');
 }
 public function test_seller_pages_require_seller_role(): void
 {
  $this->actingAs(User::factory()->create())->get('/seller')->assertForbidden();
  $this->actingAs(User::factory()->create())->get('/seller/sales')->assertForbidden();
 }
 public function test_admin_products_directory_lists_and_filters(): void
 {
  $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $seller=$this->seller();
  $pub=$this->product($seller,['title'=>'Published Widget']);
  $draft=$this->product($seller,['title'=>'Draft Widget','status'=>ProductStatus::Draft,'published_at'=>null]);
  $this->actingAs($admin)->get('/admin/products')->assertOk()->assertSee('Published Widget')->assertSee('Draft Widget');
  $this->actingAs($admin)->get('/admin/products?status=draft')->assertOk()->assertSee('Draft Widget')->assertDontSee('Published Widget');
 }
 public function test_admin_products_directory_requires_admin(): void
 {
  $this->actingAs($this->seller())->get('/admin/products')->assertForbidden();
 }
}
