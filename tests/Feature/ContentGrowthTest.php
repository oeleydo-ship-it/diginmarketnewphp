<?php
namespace Tests\Feature;
use App\Models\BlogPost;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ContentGrowthTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User { $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));return $admin; }
 public function test_admin_can_publish_a_blog_post_visible_publicly(): void
 {
  $this->actingAs($this->admin())->post('/admin/blog',['title'=>'Launching Seller Payouts','slug'=>'launching-seller-payouts','excerpt'=>'Stripe transfers are live.','body'=>'Full details about our new payout flow.','status'=>'published'])->assertRedirect('/admin/blog');
  $this->assertDatabaseHas('blog_posts',['slug'=>'launching-seller-payouts','status'=>'published']);
  $this->assertDatabaseHas('audit_logs',['action'=>'blog_post.created']);
  $this->get('/blog')->assertOk()->assertSee('Launching Seller Payouts');
  $this->get('/blog/launching-seller-payouts')->assertOk()->assertSee('payout flow');
  $this->get('/sitemap.xml')->assertOk()->assertSee('/blog/launching-seller-payouts');
 }
 public function test_draft_blog_posts_are_hidden(): void
 {
  BlogPost::create(['title'=>'Hidden Draft','slug'=>'hidden-draft','body'=>'Not ready.','status'=>'draft']);
  $this->get('/blog/hidden-draft')->assertNotFound();
  $this->get('/blog')->assertOk()->assertDontSee('Hidden Draft');
 }
 public function test_customers_cannot_manage_blog_or_menus(): void
 {
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/admin/blog',['title'=>'Nope','slug'=>'nope','body'=>'x','status'=>'published'])->assertForbidden();
  $this->actingAs($customer)->post('/admin/menus',['location'=>'footer-legal','label'=>'Bad','url'=>'/x'])->assertForbidden();
 }
 public function test_menu_items_render_in_footer_and_cache_busts_on_change(): void
 {
  $admin=$this->admin();
  $this->actingAs($admin)->post('/admin/menus',['location'=>'footer-legal','label'=>'Refund Policy','url'=>'/pages/refund-policy','display_order'=>0])->assertRedirect();
  $this->get('/')->assertOk()->assertSee('Refund Policy');
  $item=MenuItem::where('label','Refund Policy')->firstOrFail();
  $this->actingAs($admin)->put("/admin/menus/{$item->id}",['location'=>'footer-legal','label'=>'Returns Policy','url'=>'/pages/refund-policy','display_order'=>0,'is_active'=>1])->assertRedirect();
  $this->get('/')->assertOk()->assertSee('Returns Policy')->assertDontSee('Refund Policy');
  $this->actingAs($admin)->delete("/admin/menus/{$item->id}")->assertRedirect();
  $this->get('/')->assertOk()->assertDontSee('Returns Policy');
  $this->assertDatabaseHas('audit_logs',['action'=>'menu_item.deleted','entity_id'=>$item->id]);
 }
 public function test_system_health_panel_is_admin_only(): void
 {
  $this->actingAs($this->admin())->get('/admin/system')->assertOk()->assertSee('System Health')->assertSee('Pending jobs');
  $customer=User::factory()->create();
  $this->actingAs($customer)->get('/admin/system')->assertForbidden();
 }
}
