<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\NotificationPreference;
use App\Models\Product;
use App\Models\Role;
use App\Mail\ProductReviewOutcomeMail;
use App\Mail\SellerNoticeMail;
use App\Models\SellerProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProductSubmissionService;
use App\Services\SellerApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class SellerNotificationTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User
 {
  $admin = User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  return $admin;
 }

 private function application(): SellerProfile
 {
  Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']);
  $user = User::factory()->create();
  return SellerProfile::create(['user_id' => $user->id, 'display_name' => 'Bell Studio', 'username' => 'bell-studio-'.$user->id, 'country' => 'AE', 'biography' => 'Apps', 'status' => SellerStatus::Pending]);
 }

 private function draftProduct(): Product
 {
  $seller = $this->application();
  app(SellerApprovalService::class)->approve($seller, $this->admin());
  $seller->user->notifications()->delete();
  $category = Category::create(['name' => 'Apps', 'slug' => 'apps-'.uniqid()]);
  $product = Product::create(['seller_id' => $seller->user_id, 'category_id' => $category->id, 'title' => 'Bell Kit', 'slug' => 'bell-kit-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 20, 'status' => ProductStatus::Draft]);
  $product->versions()->create(['version_number' => '1.0.0', 'release_title' => 'Initial', 'status' => ProductVersionStatus::Draft]);
  return $product;
 }

 private function titles(User $user): array
 {
  return $user->fresh()->notifications()->get()->pluck('data.title')->all();
 }

 public function test_seller_is_notified_when_application_is_approved(): void
 {
  $profile = $this->application();
  app(SellerApprovalService::class)->approve($profile, $this->admin());
  $note = $profile->user->notifications()->firstOrFail();
  $this->assertSame('seller', $note->data['kind']);
  $this->assertStringContainsString('approved', $note->data['title']);
  $this->assertSame(route('seller.products.index'), $note->data['url']);
 }

 public function test_seller_is_notified_when_application_is_rejected(): void
 {
  $profile = $this->application();
  app(SellerApprovalService::class)->reject($profile, $this->admin(), 'Incomplete portfolio');
  $this->assertStringContainsString('not approved', $profile->user->notifications()->firstOrFail()->data['title']);
 }

 public function test_seller_is_notified_through_the_product_review_lifecycle(): void
 {
  $product = $this->draftProduct();
  $service = app(ProductSubmissionService::class);
  $admin = $this->admin();

  $service->submit($product, $product->seller);
  $service->requestChanges($product->fresh(), $admin, 'Add screenshots');
  $service->submit($product->fresh(), $product->seller);
  $service->approve($product->fresh(), $admin);

  $titles = $this->titles($product->seller);
  $this->assertCount(4, $titles);
  $this->assertSame(['Changes requested on “Bell Kit”', '“Bell Kit” was approved and is now live', '“Bell Kit” was submitted for review', '“Bell Kit” was submitted for review'], collect($titles)->sort()->values()->all());
  $this->assertSame(route('seller.products.edit', $product), $product->seller->notifications()->first()->data['url']);
 }

 public function test_seller_is_notified_when_a_version_is_rejected_once(): void
 {
  $product = $this->draftProduct();
  $version = $product->versions()->firstOrFail();
  $version->update(['status' => ProductVersionStatus::PendingReview]);
  $service = app(ProductSubmissionService::class);
  $service->rejectVersion($version, $this->admin(), 'Broken build');
  $service->rejectVersion($version->fresh(), $this->admin(), 'Broken build');
  $titles = $this->titles($product->seller);
  $this->assertCount(1, $titles);
  $this->assertStringContainsString('Version 1.0.0', $titles[0]);
  $this->assertStringContainsString('rejected', $titles[0]);
 }

 public function test_in_app_opt_out_suppresses_seller_notifications(): void
 {
  $profile = $this->application();
  NotificationPreference::create(['user_id' => $profile->user_id, 'in_app' => false]);
  app(SellerApprovalService::class)->approve($profile, $this->admin());
  $this->assertSame(0, $profile->user->notifications()->count());
 }

 public function test_application_decisions_are_emailed_with_the_rejection_reason(): void
 {
  Mail::fake();
  $approved = $this->application();
  app(SellerApprovalService::class)->approve($approved, $this->admin());
  Mail::assertQueued(SellerNoticeMail::class, fn ($mail) => $mail->hasTo($approved->user->email) && str_contains($mail->heading, 'approved'));
  $rejected = $this->application();
  app(SellerApprovalService::class)->reject($rejected, $this->admin(), 'Incomplete portfolio');
  Mail::assertQueued(SellerNoticeMail::class, fn ($mail) => $mail->hasTo($rejected->user->email) && $mail->notes === 'Incomplete portfolio');
 }

 public function test_submissions_and_version_decisions_are_emailed(): void
 {
  Mail::fake();
  $product = $this->draftProduct();
  app(ProductSubmissionService::class)->submit($product, $product->seller);
  Mail::assertQueued(SellerNoticeMail::class, fn ($mail) => str_contains($mail->heading, 'Submitted for review: Bell Kit'));
  $version = $product->versions()->firstOrFail();
  app(ProductSubmissionService::class)->rejectVersion($version, $this->admin(), 'Broken build');
  Mail::assertQueued(SellerNoticeMail::class, fn ($mail) => str_contains($mail->heading, 'Update rejected') && $mail->notes === 'Broken build');
 }

 public function test_admin_can_switch_off_bell_and_email_per_event(): void
 {
  Mail::fake();
  $admin = $this->admin();
  $fields = ['notifications__seller_application__app' => '0', 'notifications__seller_application__mail' => '0', 'notifications__product_reviewed__app' => '1', 'notifications__product_reviewed__mail' => '0'];
  $this->actingAs($admin)->post(route('admin.settings.sections.update', 'notifications'), $fields)->assertSessionHasNoErrors();
  $this->assertFalse(Setting::enabled('notifications.seller_application.app'));

  $profile = $this->application();
  app(SellerApprovalService::class)->approve($profile, $admin);
  $this->assertSame(0, $profile->user->notifications()->count());
  Mail::assertNotQueued(SellerNoticeMail::class);

  $category = \App\Models\Category::create(['name' => 'Apps', 'slug' => 'apps-'.uniqid()]);
  $product = Product::create(['seller_id' => $profile->user_id, 'category_id' => $category->id, 'title' => 'Quiet Kit', 'slug' => 'quiet-kit-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 20, 'status' => ProductStatus::Draft]);
  app(ProductSubmissionService::class)->submit($product, $profile->user);
  app(ProductSubmissionService::class)->approve($product->fresh(), $admin);
  Mail::assertNotQueued(ProductReviewOutcomeMail::class);
  $this->assertContains('“Quiet Kit” was approved and is now live', $this->titles($profile->user));
 }
}
