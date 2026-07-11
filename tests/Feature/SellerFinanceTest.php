<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Models\User;
use App\Services\CommissionResolver;
use App\Services\PaymentFulfillmentService;
use App\Services\SellerWalletService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class SellerFinanceTest extends TestCase
{
 use RefreshDatabase;
 private function sale():array
 {
  $seller=User::factory()->create();$buyer=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'finance-apps']);$licenseType=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);$product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Finance App','slug'=>'finance-app','short_description'=>'Finance app','description'=>str_repeat('Finance details ',5),'regular_price'=>'100.00','status'=>ProductStatus::Published,'published_at'=>now()]);$order=Order::create(['number'=>'DM-FINANCE-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>100,'total'=>100,'payment_status'=>'pending','status'=>'awaiting_payment']);$item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$licenseType->id,'product_title'=>$product->title,'seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>100,'platform_commission'=>20,'seller_earning'=>80,'total'=>100]);return compact('seller','buyer','category','product','order','item');
 }
 public function test_commission_precedence_uses_product_before_seller_category_and_global():void
 {
  $sale=$this->sale();CommissionRule::create(['scope_type'=>'global','rate'=>30]);CommissionRule::create(['scope_type'=>'category','scope_id'=>$sale['category']->id,'rate'=>25]);CommissionRule::create(['scope_type'=>'seller','scope_id'=>$sale['seller']->id,'rate'=>15]);CommissionRule::create(['scope_type'=>'product','scope_id'=>$sale['product']->id,'rate'=>10]);$result=app(CommissionResolver::class)->resolve($sale['product'],100);$this->assertSame(10.0,$result['commission']);$this->assertSame(90.0,$result['seller_earning']);
 }
 public function test_paid_sale_creates_one_pending_credit_and_clearance_moves_it_available():void
 {
  config(['marketplace.earnings_clearance_days'=>0]);$sale=$this->sale();$fulfilment=app(PaymentFulfillmentService::class);$fulfilment->fulfill($sale['order']->id,'pi_finance');$fulfilment->fulfill($sale['order']->id,'pi_finance');$wallet=SellerWallet::where('seller_id',$sale['seller']->id)->firstOrFail();$this->assertEquals(80,$wallet->pending_balance);$this->assertEquals(80,$wallet->lifetime_earnings);$this->assertDatabaseCount('wallet_transactions',1);$this->assertSame(1,app(SellerWalletService::class)->clearEligible());$wallet->refresh();$this->assertEquals(0,$wallet->pending_balance);$this->assertEquals(80,$wallet->available_balance);$this->assertDatabaseCount('wallet_transactions',2);
 }
 public function test_withdrawal_reserves_available_funds_and_rejection_reverses_them():void
 {
  $seller=User::factory()->create();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>100]);$service=app(WithdrawalService::class);$withdrawal=$service->request($wallet,60);$wallet->refresh();$this->assertEquals(40,$wallet->available_balance);$this->assertEquals(60,$wallet->reserved_balance);$service->reject($withdrawal,'Verification required.');$wallet->refresh();$this->assertEquals(100,$wallet->available_balance);$this->assertEquals(0,$wallet->reserved_balance);$this->assertSame('rejected',$withdrawal->fresh()->status);$this->assertDatabaseCount('wallet_transactions',2);
 }
 public function test_withdrawal_cannot_exceed_available_balance():void
 {
  $this->expectException(ValidationException::class);$seller=User::factory()->create();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>55]);app(WithdrawalService::class)->request($wallet,60);
 }
}