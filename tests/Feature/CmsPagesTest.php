<?php
namespace Tests\Feature;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CmsPagesTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User { $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));return $admin; }
 public function test_admin_can_create_and_publish_a_page(): void
 {
  $this->actingAs($this->admin())->post('/admin/pages',['title'=>'Terms of Service','slug'=>'terms','body'=>'These are the marketplace terms.','status'=>'published'])->assertRedirect('/admin/pages');
  $this->assertDatabaseHas('pages',['slug'=>'terms','status'=>'published']);
  $this->assertDatabaseHas('audit_logs',['action'=>'page.created']);
  $this->get('/pages/terms')->assertOk()->assertSee('Terms of Service');
  $this->get('/sitemap.xml')->assertOk()->assertSee('/pages/terms');
 }
 public function test_draft_pages_are_not_public(): void
 {
  Page::create(['title'=>'Hidden','slug'=>'hidden','body'=>'Draft content.','status'=>'draft']);
  $this->get('/pages/hidden')->assertNotFound();
  $this->get('/sitemap.xml')->assertDontSee('/pages/hidden');
 }
 public function test_unpublishing_hides_a_page(): void
 {
  $admin=$this->admin();
  $page=Page::create(['title'=>'Privacy','slug'=>'privacy','body'=>'Privacy policy.','status'=>'published','published_at'=>now()]);
  $this->get('/pages/privacy')->assertOk();
  $this->actingAs($admin)->put("/admin/pages/{$page->id}",['title'=>'Privacy','slug'=>'privacy','body'=>'Privacy policy.','status'=>'draft'])->assertRedirect('/admin/pages');
  $this->get('/pages/privacy')->assertNotFound();
  $this->assertDatabaseHas('audit_logs',['action'=>'page.updated','entity_id'=>$page->id]);
 }
 public function test_customers_cannot_manage_pages(): void
 {
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/admin/pages',['title'=>'Nope','slug'=>'nope','body'=>'x','status'=>'published'])->assertForbidden();
  $this->assertDatabaseMissing('pages',['slug'=>'nope']);
 }
}
