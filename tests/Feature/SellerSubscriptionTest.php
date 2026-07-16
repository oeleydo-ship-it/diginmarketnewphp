<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\CommissionResolver;
use App\Services\ProductSubmissionService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SellerSubscriptionTest extends TestCase
{
 use RefreshDatabase;
 private function seller(): User
 {
  $user = User::factory()->create();
  $user->roles()->attach(Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']));
  SellerProfile::create(['user_id' => $user->id, 'display_name' => 'S', 'username' => 'seller-'.$user->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  return $user;
 }
 private function plan(array $overrides = []): SubscriptionPlan
 {
  return SubscriptionPlan::create(array_merge(['name' => 'Pro', 'slug' => 'pro-'.uniqid(), 'price' => 19, 'billing_period' => 'monthly', 'commission_rate' => 10, 'listing_limit' => 2, 'is_active' => true], $overrides));
 }

 public function test_free_plan_activates_immediately_but_paid_plan_stays_pending(): void
 {
  $svc = app(SubscriptionService::class);
  $seller = $this->seller();
  $free = $svc->subscribe($seller, $this->plan(['price' => 0, 'billing_period' => 'lifetime']));
  $this->assertSame('active', $free->status);
  $paid = $svc->subscribe($seller, $this->plan(['price' => 19]));
  $this->assertSame('pending', $paid->status);
 }

 public function test_activation_sets_period_end_and_supersedes_previous(): void
 {
  $svc = app(SubscriptionService::class);
  $seller = $this->seller();
  $first = $svc->subscribe($seller, $this->plan(['price' => 0, 'billing_period' => 'lifetime']));
  $second = $svc->subscribe($seller, $this->plan(['price' => 19, 'billing_period' => 'monthly']));
  $svc->activate($second, 'ref-1');
  $this->assertSame('expired', $first->fresh()->status);
  $this->assertSame('active', $second->fresh()->status);
  $this->assertTrue($second->fresh()->ends_at->isFuture());
  $this->assertSame($second->id, $svc->activeFor($seller)->id);
 }

 public function test_active_plan_commission_override_beats_category_and_global(): void
 {
  $svc = app(SubscriptionService::class);
  $seller = $this->seller();
  $sub = $svc->subscribe($seller, $this->plan(['price' => 0, 'billing_period' => 'lifetime', 'commission_rate' => 10]));
  $category = Category::create(['name' => 'Apps', 'slug' => 'apps-'.uniqid()]);
  $product = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'K', 'slug' => 'k-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 100, 'status' => ProductStatus::Published]);
  $result = app(CommissionResolver::class)->resolve($product, 100.0);
  $this->assertEquals(10.0, $result['rate']);
  $this->assertEquals(10.0, $result['commission']);
  $this->assertEquals(90.0, $result['seller_earning']);
 }

 public function test_no_subscription_uses_default_commission(): void
 {
  $seller = $this->seller();
  $category = Category::create(['name' => 'Apps', 'slug' => 'apps-'.uniqid()]);
  $product = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'K', 'slug' => 'k-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 100, 'status' => ProductStatus::Published]);
  $result = app(CommissionResolver::class)->resolve($product, 100.0);
  $this->assertEquals((float) config('marketplace.default_commission_rate', 20), $result['rate']);
 }

 public function test_listing_limit_blocks_submission_beyond_plan_cap(): void
 {
  $svc = app(SubscriptionService::class);
  $seller = $this->seller();
  $svc->subscribe($seller, $this->plan(['price' => 0, 'billing_period' => 'lifetime', 'listing_limit' => 2]));
  $category = Category::create(['name' => 'Apps', 'slug' => 'apps-'.uniqid()]);
  // Two already-published listings fill the cap.
  Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'A', 'slug' => 'a-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 10, 'status' => ProductStatus::Published]);
  Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'B', 'slug' => 'b-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 10, 'status' => ProductStatus::Published]);
  $draft = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'C', 'slug' => 'c-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 10, 'status' => ProductStatus::Draft]);
  $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
  app(ProductSubmissionService::class)->submit($draft, $seller);
 }

 public function test_expire_due_retires_lapsed_subscriptions(): void
 {
  $svc = app(SubscriptionService::class);
  $seller = $this->seller();
  $sub = SellerSubscription::create(['user_id' => $seller->id, 'subscription_plan_id' => $this->plan()->id, 'status' => 'active', 'price' => 19, 'billing_period' => 'monthly', 'commission_rate' => 10, 'listing_limit' => 2, 'starts_at' => now()->subMonths(2), 'ends_at' => now()->subDay()]);
  $this->assertSame(1, $svc->expireDue());
  $this->assertSame('expired', $sub->fresh()->status);
 }

 public function test_seller_can_subscribe_via_http(): void
 {
  $seller = $this->seller();
  $plan = $this->plan(['price' => 0, 'billing_period' => 'lifetime']);
  $this->actingAs($seller)->post(route('seller.subscription.subscribe', $plan))->assertRedirect();
  $this->assertDatabaseHas('seller_subscriptions', ['user_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);
 }

 public function test_admin_activates_pending_subscription(): void
 {
  $svc = app(SubscriptionService::class);
  $seller = $this->seller();
  $admin = User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  $sub = $svc->subscribe($seller, $this->plan(['price' => 19]));
  $this->actingAs($admin)->post(route('admin.subscriptions.activate', $sub), ['reference' => 'PAID-123'])->assertRedirect();
  $this->assertSame('active', $sub->fresh()->status);
 }
}
