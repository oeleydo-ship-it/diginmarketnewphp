<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\LicenseType;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CouponWorkflowTest extends TestCase
{
 use RefreshDatabase;
 private function catalog(): array
 {
  $seller=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'coupon-apps']);
  $regular=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Coupon App','slug'=>'coupon-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'100.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $second=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Coupon App Two','slug'=>'coupon-app-two','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'60.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $this->app->bind(\App\Services\Gateways\StripeCheckoutGateway::class,fn()=>new class extends \App\Services\Gateways\StripeCheckoutGateway{public function isConfigured():bool{return true;}public function createCheckout(\App\Models\Order $order):array{return ['id'=>'cs_coupon_'.$order->id,'url'=>'https://checkout.stripe.test/'.$order->id];}});
  return compact('seller','regular','product','second');
 }
 public function test_percent_coupon_discounts_order_and_records_single_usage(): void
 {
  $data=$this->catalog();$coupon=Coupon::create(['code'=>'LAUNCH20','type'=>'percent','value'=>20,'is_active'=>true]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/'.$data['second']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'launch20'])->assertRedirect(route('cart.index'))->assertSessionHas('status');
  $this->get('/cart')->assertOk()->assertSee('LAUNCH20')->assertSee('128.00');
  $this->post('/checkout');
  $order=$buyer->orders()->with('items')->firstOrFail();
  $this->assertEquals(160.00,(float)$order->subtotal);
  $this->assertEquals(32.00,(float)$order->discount);
  $this->assertEquals(128.00,(float)$order->total);
  $this->assertSame('LAUNCH20',$order->coupon_code);
  $this->assertEquals(32.00,(float)$order->items->sum('discount'));
  $this->assertEquals(128.00,(float)$order->items->sum('total'));
  $service=app(PaymentFulfillmentService::class);
  $service->fulfill($order->id,'pi_coupon_1');$service->fulfill($order->id,'pi_coupon_1');
  $this->assertDatabaseCount('coupon_usages',1);
  $this->assertSame(1,$coupon->fresh()->used_count);
 }
 public function test_fixed_coupon_is_capped_and_minimum_enforced(): void
 {
  $data=$this->catalog();
  Coupon::create(['code'=>'BIG500','type'=>'fixed','value'=>500,'is_active'=>true]);
  Coupon::create(['code'=>'MIN200','type'=>'fixed','value'=>10,'min_cart_total'=>200,'is_active'=>true]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'MIN200'])->assertSessionHasErrors('coupon');
  $this->post('/cart/coupon',['code'=>'BIG500'])->assertRedirect(route('cart.index'));
  $this->post('/checkout');
  $order=$buyer->orders()->firstOrFail();
  $this->assertEquals(0.00,(float)$order->total);
  $this->assertEquals(100.00,(float)$order->discount);
 }
 public function test_expired_inactive_and_exhausted_coupons_are_rejected(): void
 {
  $data=$this->catalog();$buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  Coupon::create(['code'=>'EXPIRED','type'=>'percent','value'=>10,'is_active'=>true,'ends_at'=>now()->subDay()]);
  Coupon::create(['code'=>'PAUSED','type'=>'percent','value'=>10,'is_active'=>false]);
  Coupon::create(['code'=>'USEDUP','type'=>'percent','value'=>10,'is_active'=>true,'max_uses'=>5,'used_count'=>5]);
  foreach(['EXPIRED','PAUSED','USEDUP','NOSUCH'] as $code)$this->post('/cart/coupon',['code'=>$code])->assertSessionHasErrors('coupon');
 }
 public function test_per_user_limit_blocks_second_redemption(): void
 {
  $data=$this->catalog();$coupon=Coupon::create(['code'=>'ONCE','type'=>'percent','value'=>10,'is_active'=>true,'max_uses_per_user'=>1]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'ONCE'])->assertRedirect(route('cart.index'));
  $this->post('/checkout');
  app(PaymentFulfillmentService::class)->fulfill($buyer->orders()->firstOrFail()->id,'pi_once');
  $this->post('/cart/'.$data['second']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'ONCE'])->assertSessionHasErrors('coupon');
 }
 public function test_invalidated_coupon_is_dropped_at_checkout_reprice(): void
 {
  $data=$this->catalog();$coupon=Coupon::create(['code'=>'FLASH','type'=>'percent','value'=>50,'is_active'=>true]);
  $buyer=User::factory()->create();$this->actingAs($buyer);
  $this->post('/cart/'.$data['product']->id,['license_type_id'=>$data['regular']->id]);
  $this->post('/cart/coupon',['code'=>'FLASH'])->assertRedirect(route('cart.index'));
  $coupon->update(['is_active'=>false]);
  $this->post('/checkout');
  $order=$buyer->orders()->firstOrFail();
  $this->assertEquals(100.00,(float)$order->total);
  $this->assertEquals(0.00,(float)$order->discount);
  $this->assertNull($buyer->cart()->first()->coupon_id);
 }
 public function test_admin_manages_coupons_and_customers_cannot(): void
 {
  $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $this->actingAs($admin)->post('/admin/coupons',['code'=>'summer25','type'=>'percent','value'=>'25','max_uses_per_user'=>'2'])->assertRedirect();
  $this->assertDatabaseHas('coupons',['code'=>'SUMMER25']);
  $this->assertDatabaseHas('audit_logs',['action'=>'coupon.created']);
  $coupon=Coupon::where('code','SUMMER25')->firstOrFail();
  $this->actingAs($admin)->put("/admin/coupons/{$coupon->id}/toggle")->assertRedirect();
  $this->assertFalse($coupon->fresh()->is_active);
  $this->actingAs($admin)->post('/admin/coupons',['code'=>'TOOBIG','type'=>'percent','value'=>'150'])->assertSessionHasErrors('value');
  $this->actingAs($admin)->get('/admin/coupons')->assertOk()->assertSee('SUMMER25');
  $this->actingAs(User::factory()->create())->post('/admin/coupons',['code'=>'NOPE','type'=>'fixed','value'=>'5'])->assertForbidden();
  $this->actingAs($admin)->delete("/admin/coupons/{$coupon->id}")->assertRedirect();
  $this->assertDatabaseMissing('coupons',['code'=>'SUMMER25']);
 }
}
