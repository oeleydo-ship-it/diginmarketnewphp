<?php
namespace Tests\Feature;
use App\Contracts\CheckoutGateway;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\LicenseType;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SellerCouponTest extends TestCase
{
 use RefreshDatabase;
 private function catalog(): array
 {
  $sellerRole=Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']);
  $sellerA=User::factory()->create(['name'=>'Studio Alpha']);$sellerA->roles()->attach($sellerRole);
  $sellerB=User::factory()->create(['name'=>'Studio Beta']);$sellerB->roles()->attach($sellerRole);
  $category=Category::create(['name'=>'Apps','slug'=>'seller-coupon-apps']);
  $regular=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);
  $productA=Product::create(['seller_id'=>$sellerA->id,'category_id'=>$category->id,'title'=>'Alpha App','slug'=>'alpha-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'100.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $productB=Product::create(['seller_id'=>$sellerB->id,'category_id'=>$category->id,'title'=>'Beta App','slug'=>'beta-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'60.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $this->app->bind(CheckoutGateway::class,fn()=>new class implements CheckoutGateway{public function createCheckout(\App\Models\Order $order):array{return ['id'=>'cs_sc_'.$order->id,'url'=>'https://checkout.stripe.test/'.$order->id];}});
  return compact('sellerA','sellerB','regular','productA','productB');
 }
 public function test_seller_coupon_discounts_only_that_sellers_items_in_mixed_cart(): void
 {
  $data=$this->catalog();
  $this->actingAs($data['sellerA'])->post('/seller/coupons',['code'=>'alpha50','type'=>'percent','value'=>'50'])->assertRedirect()->assertSessionHas('status');
  $this->assertDatabaseHas('coupons',['code'=>'ALPHA50','seller_id'=>$data['sellerA']->id]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['productA']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/'.$data['productB']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'ALPHA50'])->assertRedirect(route('cart.index'));
  $this->get('/cart')->assertOk()->assertSee('ALPHA50')->assertSee('Studio Alpha');
  $this->post('/checkout');
  $order=$buyer->orders()->with('items')->firstOrFail();
  $this->assertEquals(160.00,(float)$order->subtotal);
  $this->assertEquals(50.00,(float)$order->discount);
  $this->assertEquals(110.00,(float)$order->total);
  $itemA=$order->items->firstWhere('product_id',$data['productA']->id);
  $itemB=$order->items->firstWhere('product_id',$data['productB']->id);
  $this->assertEquals(50.00,(float)$itemA->discount);
  $this->assertEquals(50.00,(float)$itemA->total);
  $this->assertEquals(0.00,(float)$itemB->discount);
  $this->assertEquals(60.00,(float)$itemB->total);
 }
 public function test_seller_coupon_rejected_when_cart_has_no_eligible_items(): void
 {
  $data=$this->catalog();
  Coupon::create(['code'=>'ALPHAONLY','seller_id'=>$data['sellerA']->id,'type'=>'percent','value'=>25,'is_active'=>true]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['productB']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'ALPHAONLY'])->assertSessionHasErrors('coupon');
  $this->assertNull($buyer->cart()->first()->coupon_id);
 }
 public function test_seller_coupon_min_total_measured_on_eligible_subtotal(): void
 {
  $data=$this->catalog();
  Coupon::create(['code'=>'ALPHAMIN','seller_id'=>$data['sellerA']->id,'type'=>'fixed','value'=>10,'min_cart_total'=>150,'is_active'=>true]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['productA']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/'.$data['productB']->id,['license_type_id'=>$data['regular']->id]);
  // Cart totals $160, but only $100 belongs to Studio Alpha — below the $150 floor.
  $this->post('/cart/coupon',['code'=>'ALPHAMIN'])->assertSessionHasErrors('coupon');
 }
 public function test_sellers_cannot_manage_other_sellers_or_platform_coupons(): void
 {
  $data=$this->catalog();
  $foreign=Coupon::create(['code'=>'BETACODE','seller_id'=>$data['sellerB']->id,'type'=>'percent','value'=>10,'is_active'=>true]);
  $platform=Coupon::create(['code'=>'SITEWIDE','type'=>'percent','value'=>10,'is_active'=>true]);
  $this->actingAs($data['sellerA'])->put("/seller/coupons/{$foreign->id}/toggle")->assertNotFound();
  $this->actingAs($data['sellerA'])->delete("/seller/coupons/{$foreign->id}")->assertNotFound();
  $this->actingAs($data['sellerA'])->put("/seller/coupons/{$platform->id}/toggle")->assertNotFound();
  $this->actingAs($data['sellerA'])->get('/seller/coupons')->assertOk()->assertDontSee('BETACODE')->assertDontSee('SITEWIDE');
  $this->actingAs(User::factory()->create())->post('/seller/coupons',['code'=>'NOPE','type'=>'fixed','value'=>'5'])->assertForbidden();
 }
 public function test_platform_coupons_still_discount_whole_cart(): void
 {
  $data=$this->catalog();
  Coupon::create(['code'=>'EVERYONE','type'=>'percent','value'=>10,'is_active'=>true]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['productA']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/'.$data['productB']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'EVERYONE'])->assertRedirect(route('cart.index'));
  $this->post('/checkout');
  $order=$buyer->orders()->with('items')->firstOrFail();
  $this->assertEquals(16.00,(float)$order->discount);
  $this->assertEquals(144.00,(float)$order->total);
  $this->assertEquals(16.00,(float)$order->items->sum('discount'));
 }
}
