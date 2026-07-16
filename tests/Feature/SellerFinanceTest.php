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
 private function seller():User
 {
  $u=User::factory()->create();$u->roles()->attach(\App\Models\Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']));
  \App\Models\SellerProfile::create(['user_id'=>$u->id,'display_name'=>'S','username'=>'s-'.$u->id,'country'=>'AE','biography'=>'x','status'=>\App\Enums\SellerStatus::Approved]);
  return $u;
 }
 public function test_seller_saves_default_payout_method():void
 {
  $seller=$this->seller();
  $this->actingAs($seller)->put('/seller/payout-settings',['payout_method'=>'paypal','paypal_email'=>'me@example.com'])->assertRedirect()->assertSessionHas('status');
  $profile=$seller->sellerProfile->fresh();
  $this->assertTrue($profile->hasDefaultPayout());
  $this->assertSame('paypal',$profile->default_payout_method);
  $this->assertSame('me@example.com',$profile->default_payout_details['email']);
 }
 public function test_use_default_withdraws_with_only_an_amount():void
 {
  $seller=$this->seller();SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>300]);
  $seller->sellerProfile->update(['default_payout_method'=>'bank','default_payout_details'=>['bank_name'=>'Acme','account_name'=>'S','account_number'=>'55557777']]);
  $this->actingAs($seller)->post('/seller/withdrawals',['use_default'=>'1','amount'=>'90'])->assertRedirect();
  $wd=\App\Models\WithdrawalRequest::firstOrFail();
  $this->assertSame('bank',$wd->payout_method);
  $this->assertSame('Acme',$wd->payout_details['bank_name']);
 }
 public function test_save_default_checkbox_on_withdrawal_persists_method():void
 {
  $seller=$this->seller();SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>300]);
  $this->actingAs($seller)->post('/seller/withdrawals',['amount'=>'90','payout_method'=>'paypal','paypal_email'=>'save@example.com','save_default'=>'1'])->assertRedirect();
  $this->assertSame('paypal',$seller->sellerProfile->fresh()->default_payout_method);
 }
 public function test_use_default_without_saved_method_falls_through_to_validation():void
 {
  $seller=$this->seller();SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>300]);
  // No default saved: use_default is ignored, so the full form rules apply and payout_method is required.
  $this->actingAs($seller)->post('/seller/withdrawals',['use_default'=>'1','amount'=>'90'])->assertSessionHasErrors('payout_method');
 }
 public function test_seller_requests_paypal_withdrawal_and_details_are_stored():void
 {
  $seller=$this->seller();SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>200]);
  $this->actingAs($seller)->post('/seller/withdrawals',['amount'=>'80','payout_method'=>'paypal','paypal_email'=>'pay@example.com'])->assertRedirect();
  $wd=\App\Models\WithdrawalRequest::firstOrFail();
  $this->assertSame('paypal',$wd->payout_method);
  $this->assertSame('pay@example.com',$wd->payout_details['email']);
  $this->assertSame('PayPal',$wd->methodLabel());
 }
 public function test_paypal_withdrawal_requires_email():void
 {
  $seller=$this->seller();SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>200]);
  $this->actingAs($seller)->post('/seller/withdrawals',['amount'=>'80','payout_method'=>'paypal'])->assertSessionHasErrors('paypal_email');
 }
 public function test_bank_withdrawal_stores_account_details():void
 {
  $seller=$this->seller();SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>200]);
  $this->actingAs($seller)->post('/seller/withdrawals',['amount'=>'80','payout_method'=>'bank','bank_name'=>'Acme Bank','account_name'=>'A Seller','account_number'=>'123456789'])->assertRedirect();
  $wd=\App\Models\WithdrawalRequest::firstOrFail();
  $this->assertSame('bank',$wd->payout_method);
  $this->assertSame('Acme Bank',$wd->payout_details['bank_name']);
  $this->assertStringContainsString('6789',$wd->payoutSummary());
 }
 public function test_admin_marks_manual_payout_paid_and_wallet_settles():void
 {
  $admin=User::factory()->create();$admin->roles()->attach(\App\Models\Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $seller=$this->seller();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>200]);
  $wd=app(WithdrawalService::class)->request($wallet,100,'paypal',['email'=>'pay@example.com']);
  $wallet->refresh();$this->assertEquals(100,$wallet->reserved_balance);
  $this->actingAs($admin)->post('/admin/withdrawals/'.$wd->id.'/mark-paid',['reference'=>'PP-TXN-123'])->assertRedirect();
  $wd->refresh();$wallet->refresh();
  $this->assertSame('paid',$wd->status);
  $this->assertSame('PP-TXN-123',$wd->payout_reference);
  $this->assertEquals(0,$wallet->reserved_balance);
  $this->assertEquals(100,$wallet->withdrawn_balance);
 }
 public function test_stripe_approve_rejects_a_paypal_request():void
 {
  $this->expectException(ValidationException::class);
  $seller=$this->seller();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>200]);
  $wd=app(WithdrawalService::class)->request($wallet,100,'paypal',['email'=>'pay@example.com']);
  app(WithdrawalService::class)->approve($wd,$seller,'');
 }
}