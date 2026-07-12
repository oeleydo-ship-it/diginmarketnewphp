<?php
namespace Tests\Feature;
use App\Contracts\CheckoutGateway;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Http\Middleware\TrackAffiliateReferral;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AffiliateProgramTest extends TestCase
{
 use RefreshDatabase;
 private function catalog(): array
 {
  $seller=User::factory()->create();
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Affiliate Studio','username'=>'affiliate-studio','country'=>'AE','biography'=>'Seller','status'=>'approved']);
  $category=Category::create(['name'=>'Affiliate Apps','slug'=>'affiliate-apps']);
  $type=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Referral Kit','slug'=>'referral-kit','short_description'=>'Refer.','description'=>str_repeat('Complete referral kit. ',4),'regular_price'=>'100.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $product->versions()->create(['version_number'=>'1.0.0','release_title'=>'Stable','status'=>ProductVersionStatus::Published,'published_at'=>now()]);
  $this->app->bind(CheckoutGateway::class,fn()=>new class implements CheckoutGateway{public function createCheckout(\App\Models\Order $order):array{return ['id'=>'cs_aff_'.$order->id,'url'=>'https://checkout.stripe.test/session/'.$order->id];}});
  return compact('seller','type','product');
 }
 public function test_enrolling_creates_profile_and_referral_click_sets_cookie(): void
 {
  $affiliateUser=User::factory()->create();
  $this->actingAs($affiliateUser)->post('/affiliates')->assertRedirect();
  $profile=$affiliateUser->fresh()->affiliateProfile()->firstOrFail();
  $this->get('/?ref='.$profile->code)->assertCookie(TrackAffiliateReferral::COOKIE,$profile->code);
  $this->assertSame(1,$profile->fresh()->clicks);
 }
 public function test_referred_paid_order_credits_affiliate_share_of_commission_once(): void
 {
  $d=$this->catalog();
  $affiliate=app(AffiliateService::class)->enroll(User::factory()->create());
  $buyer=User::factory()->create();
  $this->actingAs($buyer)->post('/cart/'.$d['product']->id,['license_type_id'=>$d['type']->id]);
  $this->withCookie(TrackAffiliateReferral::COOKIE,$affiliate->code)->post('/checkout');
  $order=$buyer->orders()->firstOrFail();
  $this->assertSame($affiliate->id,$order->affiliate_profile_id);
  $service=app(PaymentFulfillmentService::class);
  $service->fulfill($order->id,'pi_aff');
  $service->fulfill($order->id,'pi_aff');
  $commission=(float)$order->items()->sum('platform_commission');
  $expected=round($commission*0.2,2);
  $this->assertDatabaseCount('affiliate_earnings',1);
  $this->assertDatabaseHas('affiliate_earnings',['order_id'=>$order->id,'affiliate_profile_id'=>$affiliate->id,'status'=>'pending']);
  $this->assertEquals($expected,(float)$affiliate->fresh()->total_earnings);
  $this->assertSame(1,$affiliate->fresh()->referred_orders);
 }
 public function test_self_referral_is_not_credited(): void
 {
  $d=$this->catalog();
  $buyer=User::factory()->create();
  $profile=app(AffiliateService::class)->enroll($buyer);
  $this->actingAs($buyer)->post('/cart/'.$d['product']->id,['license_type_id'=>$d['type']->id]);
  $this->withCookie(TrackAffiliateReferral::COOKIE,$profile->code)->post('/checkout');
  $order=$buyer->orders()->firstOrFail();
  $this->assertNull($order->affiliate_profile_id);
  app(PaymentFulfillmentService::class)->fulfill($order->id,'pi_self');
  $this->assertDatabaseCount('affiliate_earnings',0);
 }
}
