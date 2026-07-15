<?php
namespace Tests\Feature;
use App\Models\BlogPost;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\DatabaseSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminSettingsTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User { $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));return $admin; }
 public function test_settings_page_shows_all_sections(): void
 {
  $this->actingAs($this->admin())->get('/admin/settings')->assertOk()
   ->assertSee('Commerce & Finance')->assertSee('Features')->assertSee('Social Links')
   ->assertSee('Default commission %')->assertSee('Customer registration');
 }
 public function test_commerce_section_persists_and_applies_to_config(): void
 {
  $this->actingAs($this->admin())->post('/admin/settings/sections/commerce',[
   'commerce__default_commission_rate'=>'25.5','commerce__affiliate_commission_rate'=>'15',
   'commerce__withdrawal_fee_rate'=>'2.5','commerce__minimum_withdrawal'=>'75','commerce__earnings_clearance_days'=>'21',
  ])->assertRedirect()->assertSessionHas('status');
  $this->assertDatabaseHas('settings',['key'=>'commerce.default_commission_rate','value'=>'25.5']);
  DatabaseSettings::apply();
  $this->assertSame(25.5,config('marketplace.default_commission_rate'));
  $this->assertSame(21,config('marketplace.earnings_clearance_days'));
  $this->assertSame(75.0,config('marketplace.minimum_withdrawal'));
  $this->assertDatabaseHas('audit_logs',['action'=>'settings.section_updated']);
 }
 public function test_commerce_section_rejects_out_of_range_values(): void
 {
  $this->actingAs($this->admin())->post('/admin/settings/sections/commerce',['commerce__default_commission_rate'=>'150'])->assertSessionHasErrors('commerce__default_commission_rate');
 }
 public function test_disabling_registration_blocks_signup(): void
 {
  Role::firstOrCreate(['slug'=>'customer'],['name'=>'Customer']);
  Setting::put('features.registration','0','features');
  $this->post('/register',['name'=>'Blocked User','email'=>'blocked@example.test','password'=>'Password!234','password_confirmation'=>'Password!234'])->assertForbidden();
  Setting::put('features.registration','1','features');
  $this->post('/register',['name'=>'Allowed User','email'=>'allowed@example.test','password'=>'Password!234','password_confirmation'=>'Password!234'])->assertRedirect('/dashboard');
 }
 public function test_disabling_registration_hides_signup_entry_points(): void
 {
  Setting::put('features.registration','0','features');
  $this->get('/register')->assertRedirect(route('login'))->assertSessionHas('registration_closed');
  $this->followingRedirects()->get('/register')->assertOk()->assertSee('Registration unavailable');
  $this->get('/login')->assertOk()->assertDontSee('Create an account');
  $this->get('/')->assertOk()->assertDontSee(route('register'));
  Setting::put('features.registration','1','features');
  $this->get('/register')->assertOk();
  $this->get('/login')->assertOk()->assertSee('Create an account');
 }
 public function test_disabling_comments_hides_the_comment_form(): void
 {
  $category=\App\Models\Category::create(['name'=>'Settings Apps','slug'=>'settings-apps']);
  $seller=User::factory()->create();
  $product=\App\Models\Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Toggle App','slug'=>'toggle-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'10.00','status'=>\App\Enums\ProductStatus::Published,'published_at'=>now()]);
  $this->get('/products/toggle-app')->assertOk()->assertDontSee('Comments are currently disabled');
  Setting::put('features.comments','0','features');
  $this->get('/products/toggle-app')->assertOk()->assertSee('Comments are currently disabled');
 }
 public function test_disabling_seller_applications_blocks_the_apply_flow(): void
 {
  Setting::put('features.seller_applications','0','features');
  $user=User::factory()->create();
  $this->actingAs($user)->get('/sell/apply')->assertForbidden();
  $this->actingAs($user)->post('/sell/apply',['display_name'=>'Studio','username'=>'blocked-studio','country'=>'AE','biography'=>'Long enough biography text.'])->assertForbidden();
 }
 public function test_disabling_blog_hides_public_blog(): void
 {
  BlogPost::create(['title'=>'Visible','slug'=>'visible-post','body'=>'Content.','status'=>'published','published_at'=>now()]);
  $this->get('/blog')->assertOk();
  Setting::put('features.blog','0','features');
  $this->get('/blog')->assertNotFound();
  $this->get('/blog/visible-post')->assertNotFound();
 }
 public function test_social_links_render_in_the_footer_when_configured(): void
 {
  $this->get('/')->assertOk()->assertDontSee('aria-label="X (Twitter)"',false);
  Setting::put('social.twitter','https://x.com/diginmarket','social');
  $this->get('/')->assertOk()->assertSee('https://x.com/diginmarket',false)->assertSee('aria-label="X (Twitter)"',false);
 }
 public function test_feature_toggles_require_administrator(): void
 {
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/admin/settings/sections/features',['features__registration'=>'0'])->assertForbidden();
 }
}
