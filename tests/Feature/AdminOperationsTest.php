<?php
namespace Tests\Feature;
use App\Contracts\PayoutGateway;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\SellerWallet;
use App\Models\StripeConnectedAccount;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminOperationsTest extends TestCase
{
 use RefreshDatabase;
 private function role(string $slug): Role { return Role::firstOrCreate(['slug'=>$slug],['name'=>str($slug)->headline()]); }
 private function admin(): User { $admin=User::factory()->create();$admin->roles()->attach($this->role('administrator'));return $admin; }
 private function fakePayouts(): void { $this->app->bind(PayoutGateway::class,fn()=>new class implements PayoutGateway{public function transfer(WithdrawalRequest $withdrawal,string $stripeAccountId,string $currency):array{return ['id'=>'tr_test_'.$withdrawal->id];}}); }
 public function test_admin_dashboard_shows_operational_metrics(): void
 {
  $buyer=User::factory()->create();Order::create(['number'=>'DM-ADMIN-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>120,'total'=>120,'payment_status'=>'paid','status'=>'completed']);
  $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('120.00')->assertSee('Work queues');
  $customer=User::factory()->create();$customer->roles()->attach($this->role('customer'));
  $this->actingAs($customer)->get('/admin')->assertForbidden();
 }
 public function test_seller_rejection_records_reason_and_audit(): void
 {
  $applicant=User::factory()->create();$profile=SellerProfile::create(['user_id'=>$applicant->id,'display_name'=>'Studio','username'=>'reject-studio','country'=>'AE','biography'=>'Bio','status'=>SellerStatus::Pending]);
  $this->actingAs($this->admin())->post("/admin/sellers/{$profile->id}/reject",['reason'=>'Identity documents unreadable.'])->assertRedirect();
  $profile->refresh();$this->assertSame(SellerStatus::Rejected,$profile->status);$this->assertSame('Identity documents unreadable.',$profile->rejection_reason);
  $this->assertDatabaseHas('audit_logs',['action'=>'seller.rejected','entity_id'=>$profile->id]);
 }
 public function test_product_changes_request_returns_product_to_seller(): void
 {
  $seller=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'admin-apps']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Pending App','slug'=>'pending-app','short_description'=>'Pending app','description'=>str_repeat('Details ',10),'regular_price'=>'40.00','status'=>ProductStatus::Submitted,'submitted_at'=>now()]);
  $product->reviewSubmissions()->create(['submitted_by'=>$seller->id,'status'=>'submitted','submitted_at'=>now()]);
  $this->actingAs($this->admin())->post("/admin/products/{$product->id}/request-changes",['notes'=>'Add documentation and screenshots.'])->assertRedirect();
  $this->assertSame(ProductStatus::ChangesRequested,$product->fresh()->status);
  $this->assertSame('changes_requested',$product->reviewSubmissions()->latest()->first()->status);
  $this->assertDatabaseHas('audit_logs',['action'=>'product.changes_requested','entity_id'=>$product->id]);
 }
 public function test_withdrawal_approval_pays_through_stripe_transfer(): void
 {
  $this->fakePayouts();
  $seller=User::factory()->create();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>100]);
  StripeConnectedAccount::create(['seller_id'=>$seller->id,'stripe_account_id'=>'acct_test_1','status'=>'enabled','details_submitted'=>true,'charges_enabled'=>true,'payouts_enabled'=>true]);
  $withdrawal=app(WithdrawalService::class)->request($wallet,60);
  $this->actingAs($this->admin())->post("/admin/withdrawals/{$withdrawal->id}/approve",['note'=>'Verified.'])->assertRedirect();
  $wallet->refresh();$withdrawal->refresh();
  $this->assertSame('paid',$withdrawal->status);$this->assertSame('tr_test_'.$withdrawal->id,$withdrawal->stripe_transfer_id);
  $this->assertEquals(0,$wallet->reserved_balance);$this->assertEquals(60,$wallet->withdrawn_balance);$this->assertEquals(40,$wallet->available_balance);
  $this->assertDatabaseHas('wallet_transactions',['type'=>'withdrawal_paid','amount'=>-60]);
  $this->assertDatabaseHas('audit_logs',['action'=>'withdrawal.paid','entity_id'=>$withdrawal->id]);
  $this->actingAs($this->admin())->post("/admin/withdrawals/{$withdrawal->id}/approve")->assertRedirect();
  $this->assertEquals(60,$wallet->fresh()->withdrawn_balance);$this->assertDatabaseCount('wallet_transactions',2);
 }
 public function test_withdrawal_approval_requires_payout_enabled_account(): void
 {
  $this->fakePayouts();
  $seller=User::factory()->create();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>100]);
  $withdrawal=app(WithdrawalService::class)->request($wallet,60);
  $this->actingAs($this->admin())->from('/admin/withdrawals')->post("/admin/withdrawals/{$withdrawal->id}/approve")->assertRedirect('/admin/withdrawals')->assertSessionHasErrors('withdrawal');
  $this->assertSame('pending',$withdrawal->fresh()->status);$this->assertEquals(60,$wallet->fresh()->reserved_balance);
 }
 public function test_refund_rejection_leaves_order_intact(): void
 {
  $seller=User::factory()->create();$buyer=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'refund-apps']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Refund App','slug'=>'refund-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'50.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $licenseType=\App\Models\LicenseType::create(['name'=>'Regular','slug'=>'regular-admin','description'=>'Regular']);
  $order=Order::create(['number'=>'DM-REFUND-9','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>50,'total'=>50,'payment_status'=>'paid','status'=>'completed']);
  $item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$licenseType->id,'product_title'=>$product->title,'seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>50,'platform_commission'=>10,'seller_earning'=>40,'total'=>50]);
  $refund=RefundRequest::create(['number'=>'RF-TEST-1','user_id'=>$buyer->id,'order_id'=>$order->id,'order_item_id'=>$item->id,'product_id'=>$product->id,'seller_id'=>$seller->id,'reason'=>'not_as_described','description'=>'Feature missing.','requested_amount'=>50,'status'=>'submitted']);
  $this->actingAs($this->admin())->post("/admin/refunds/{$refund->id}/reject",['decision'=>'Product matches its listing.'])->assertRedirect();
  $refund->refresh();$this->assertSame('rejected',$refund->status);$this->assertSame('Product matches its listing.',$refund->administrator_decision);
  $this->assertSame('paid',$order->fresh()->payment_status);
  $this->assertDatabaseHas('audit_logs',['action'=>'refund.rejected','entity_id'=>$refund->id]);
 }
 public function test_settings_update_is_audited(): void
 {
  $this->actingAs($this->admin())->put('/admin/settings',['group'=>'marketplace','key'=>'marketplace.support_email','value'=>'help@diginmarket.test'])->assertRedirect();
  $this->assertDatabaseHas('settings',['key'=>'marketplace.support_email','value'=>'help@diginmarket.test']);
  $this->assertDatabaseHas('audit_logs',['action'=>'setting.updated']);
 }
 public function test_user_suspension_is_audited_and_self_protected(): void
 {
  $admin=$this->admin();$target=User::factory()->create();
  $this->actingAs($admin)->put("/admin/users/{$target->id}/status",['status'=>'suspended'])->assertRedirect();
  $this->assertSame('suspended',$target->fresh()->status);
  $this->assertDatabaseHas('audit_logs',['action'=>'user.status_changed','entity_id'=>$target->id]);
  $this->actingAs($admin)->put("/admin/users/{$admin->id}/status",['status'=>'suspended'])->assertStatus(422);
 }
 public function test_admin_directories_require_administrator_role(): void
 {
  $customer=User::factory()->create();$customer->roles()->attach($this->role('customer'));
  foreach(['/admin/users','/admin/orders','/admin/audits','/admin/settings','/admin/sellers','/admin/refunds','/admin/withdrawals','/admin/pages'] as $path)$this->actingAs($customer)->get($path)->assertForbidden();
  $admin=$this->admin();
  foreach(['/admin/users','/admin/orders','/admin/audits','/admin/settings','/admin/sellers','/admin/refunds','/admin/withdrawals','/admin/pages'] as $path)$this->actingAs($admin)->get($path)->assertOk();
 }
}
