<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\NotificationPreference;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\PaymentFulfillmentService;
use App\Services\MarketplaceMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminNotificationTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User
 {
  $admin = User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  return $admin;
 }

 private function paidSale(): Order
 {
  $seller = User::factory()->create();
  $category = Category::create(['name' => 'Apps', 'slug' => 'apps-'.uniqid()]);
  $license = LicenseType::firstOrCreate(['slug' => 'regular'], ['name' => 'Regular License', 'description' => 'r']);
  $product = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Notify Kit', 'slug' => 'notify-kit-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 50, 'status' => ProductStatus::Published]);
  $buyer = User::factory()->create();
  $order = Order::create(['number' => 'DM-NOTIF-'.uniqid(), 'user_id' => $buyer->id, 'currency' => 'USD', 'subtotal' => 50, 'total' => 50, 'payment_status' => 'pending', 'status' => 'awaiting_payment']);
  $order->items()->create(['product_id' => $product->id, 'seller_id' => $seller->id, 'license_type_id' => $license->id, 'product_title' => 'Notify Kit', 'seller_name' => 'S', 'license_name' => 'Regular License', 'unit_price' => 50, 'platform_commission' => 10, 'seller_earning' => 40, 'total' => 50]);
  return $order;
 }

 public function test_paid_order_notifies_every_admin(): void
 {
  $adminOne = $this->admin();
  $adminTwo = $this->admin();
  $order = $this->paidSale();
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_notify');
  $this->assertSame(1, $adminOne->unreadNotifications()->count());
  $this->assertSame(1, $adminTwo->unreadNotifications()->count());
  $this->assertStringContainsString($order->number, $adminOne->notifications()->first()->data['title']);
  // Idempotent fulfilment does not duplicate the alert.
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_notify');
  $this->assertSame(1, $adminOne->unreadNotifications()->count());
 }

 public function test_paid_order_notifies_buyer_and_seller_in_their_bells(): void
 {
  $admin = $this->admin();
  $order = $this->paidSale();
  $buyer = $order->user;
  $seller = User::findOrFail($order->items->first()->seller_id);
  $seller->roles()->attach(Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']));

  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_everyone');

  $this->assertSame(1, $admin->unreadNotifications()->count());
  $this->assertSame(1, $buyer->unreadNotifications()->count());
  $this->assertSame(1, $seller->unreadNotifications()->count());
  $this->actingAs($buyer)->get('/purchases')->assertOk()->assertSee('Order '.$order->number.' is confirmed');
  $this->actingAs($seller)->get('/seller/sales')->assertOk()->assertSee('New sale in order '.$order->number);

  $sellerAlert = $seller->notifications()->firstOrFail();
  $this->get(route('notifications.open', $sellerAlert->id))->assertRedirect(route('seller.sales'));
  $this->assertNotNull($sellerAlert->fresh()->read_at);
  $buyerAlert = $buyer->notifications()->firstOrFail();
  $this->actingAs($buyer)->get(route('notifications.open', $sellerAlert->id))->assertNotFound();
  $this->get(route('notifications.open', $buyerAlert->id))->assertRedirect(route('purchases.show', $order));
  $this->post(route('notifications.read-all'))->assertRedirect();
  $this->assertSame(0, $buyer->unreadNotifications()->count());

  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_everyone');
  $this->assertSame(1, $buyer->notifications()->count());
  $this->assertSame(1, $seller->notifications()->count());
 }

 public function test_in_app_preference_skips_seller_order_alert_without_hiding_admin_or_buyer_alerts(): void
 {
  $admin = $this->admin();
  $order = $this->paidSale();
  $seller = User::findOrFail($order->items->first()->seller_id);
  NotificationPreference::create(['user_id' => $seller->id, 'in_app' => false]);

  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_no_seller_bell');

  $this->assertSame(1, $admin->unreadNotifications()->count());
  $this->assertSame(1, $order->user->unreadNotifications()->count());
  $this->assertSame(0, $seller->unreadNotifications()->count());
 }

 public function test_mail_queue_failure_does_not_hide_paid_order_alerts(): void
 {
  $admin = $this->admin();
  $order = $this->paidSale();
  $seller = User::findOrFail($order->items->first()->seller_id);
  $mailer = $this->mock(MarketplaceMailer::class);
  $mailer->shouldReceive('orderPaid')->once()->andThrow(new \RuntimeException('Queue unavailable'));

  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_mail_failure');

  $this->assertSame('paid', $order->fresh()->payment_status);
  $this->assertSame(1, $admin->unreadNotifications()->count());
  $this->assertSame(1, $order->user->unreadNotifications()->count());
  $this->assertSame(1, $seller->unreadNotifications()->count());
 }

 public function test_bell_shows_unread_badge_and_dropdown_items(): void
 {
  $admin = $this->admin();
  $order = $this->paidSale();
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_badge');
  $response = $this->actingAs($admin)->get('/admin');
  $response->assertOk()->assertSee('New paid order '.$order->number, false)->assertSee('Mark all read');
 }

 public function test_opening_a_notification_marks_it_read_and_redirects(): void
 {
  $admin = $this->admin();
  $order = $this->paidSale();
  app(PaymentFulfillmentService::class)->fulfill($order->id, 'pi_open');
  $notification = $admin->notifications()->firstOrFail();
  $this->actingAs($admin)->get(route('admin.notifications.open', $notification->id))->assertRedirect(route('admin.orders.index'));
  $this->assertNotNull($notification->fresh()->read_at);
 }

 public function test_mark_all_read_clears_the_badge(): void
 {
  $admin = $this->admin();
  app(PaymentFulfillmentService::class)->fulfill($this->paidSale()->id, 'pi_a');
  app(PaymentFulfillmentService::class)->fulfill($this->paidSale()->id, 'pi_b');
  $this->assertSame(2, $admin->unreadNotifications()->count());
  $this->actingAs($admin)->post(route('admin.notifications.read-all'))->assertRedirect();
  $this->assertSame(0, $admin->unreadNotifications()->count());
 }

 public function test_admins_cannot_open_each_others_notifications(): void
 {
  $adminOne = $this->admin();
  $adminTwo = $this->admin();
  app(PaymentFulfillmentService::class)->fulfill($this->paidSale()->id, 'pi_priv');
  $foreign = $adminOne->notifications()->firstOrFail();
  $this->actingAs($adminTwo)->get(route('admin.notifications.open', $foreign->id))->assertNotFound();
 }

 public function test_seller_application_notifies_admins(): void
 {
  $admin = $this->admin();
  $applicant = User::factory()->create();
  $this->actingAs($applicant)->post('/sell/apply', ['full_name' => 'App Licant', 'display_name' => 'Applicant Studio', 'username' => 'applicant-studio', 'country' => 'AE', 'address' => '1 Market St', 'city' => 'Dubai', 'business_name' => 'Applicant LLC', 'biography' => str_repeat('Experienced creator. ', 3)])->assertRedirect();
  $this->assertSame(1, $admin->unreadNotifications()->count());
  $this->assertStringContainsString('Applicant Studio', $admin->notifications()->first()->data['title']);
 }
}
