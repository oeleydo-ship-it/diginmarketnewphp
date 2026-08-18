<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BusinessLicenseTest extends TestCase
{
 use RefreshDatabase;
 private function catalog(bool $businessEnabled=true): array
 {
  $seller=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'license-apps']);
  LicenseType::create(['name'=>'Regular License','slug'=>'regular','description'=>'Single end product, end users not charged.']);
  $business=LicenseType::create(['name'=>'Business License','slug'=>'extended','description'=>'End users may be charged.']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'License App','slug'=>'license-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'50.00','extended_price'=>'200.00','business_license_enabled'=>$businessEnabled,'status'=>ProductStatus::Published,'published_at'=>now()]);
  return compact('seller','product','business');
 }
 public function test_product_page_shows_business_license_when_enabled(): void
 {
  $this->catalog();
  $this->get('/products/license-app')->assertOk()->assertSee('Business License')->assertSee('200');
 }
 public function test_product_page_hides_business_license_when_disabled(): void
 {
  $this->catalog(false);
  $this->get('/products/license-app')->assertOk()->assertDontSee('Business License')->assertSee('Regular License');
 }
 public function test_cart_rejects_business_license_when_disabled(): void
 {
  $data=$this->catalog(false);
  $this->actingAs(User::factory()->create())->post('/cart/'.$data['product']->id,['license_type_id'=>$data['business']->id])->assertStatus(422);
  $this->assertDatabaseCount('cart_items',0);
 }
 public function test_owner_can_add_another_regular_or_business_license(): void
 {
  $data=$this->catalog();
  $regular=LicenseType::where('slug','regular')->firstOrFail();
  $buyer=User::factory()->create();
  $order=Order::create(['number'=>'DM-OWN-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>50,'total'=>50,'payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);
  $item=$order->items()->create(['product_id'=>$data['product']->id,'seller_id'=>$data['seller']->id,'license_type_id'=>$regular->id,'product_title'=>'License App','seller_name'=>$data['seller']->name,'license_name'=>'Regular License','unit_price'=>50,'platform_commission'=>10,'seller_earning'=>40,'total'=>50]);
  License::create(['license_key'=>'DM-OWN-LICENSE','product_id'=>$data['product']->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'license_type_id'=>$regular->id,'status'=>'active']);
  $this->actingAs($buyer)->get('/products/license-app')->assertOk()
   ->assertSee('You already have a license')
   ->assertSee('Buy another license')
   ->assertSee('Regular License')
   ->assertSee('Business License');
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$regular->id])->assertRedirect('/cart');
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['business']->id])->assertRedirect('/cart');
  $this->assertDatabaseHas('cart_items',['product_id'=>$data['product']->id,'license_type_id'=>$data['business']->id]);
 }
 public function test_seller_can_toggle_business_license_from_edit_form(): void
 {
  $data=$this->catalog();
  $seller=$data['seller'];$seller->roles()->attach(\App\Models\Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']));
  $data['product']->update(['status'=>ProductStatus::Draft,'published_at'=>null]);
  $this->actingAs($seller)->put('/seller/products/'.$data['product']->id,['category_id'=>$data['product']->category_id,'title'=>'License App','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'50.00','extended_price'=>'200.00'])->assertRedirect();
  $this->assertFalse($data['product']->fresh()->business_license_enabled);
  $this->actingAs($seller)->put('/seller/products/'.$data['product']->id,['category_id'=>$data['product']->category_id,'title'=>'License App','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'50.00','extended_price'=>'200.00','business_license_enabled'=>'1'])->assertRedirect();
  $this->assertTrue($data['product']->fresh()->business_license_enabled);
 }
}
