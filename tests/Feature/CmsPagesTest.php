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
 public function test_admin_pages_index_includes_homepage_editor(): void
 {
  $this->actingAs($this->admin())->get('/admin/pages')->assertOk()->assertSee('Homepage')->assertSee('Edit Homepage');
 }
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
 public function test_admin_can_update_homepage_content_and_homepage_uses_it(): void
 {
  $admin=$this->admin();
  $payload=[
   'title'=>'Digital products for fast-moving teams.',
   'excerpt'=>'Launch client projects with curated scripts, UI kits, and marketplace-ready tools.',
   'body'=>'<p>Homepage custom content block.</p>',
   'meta_title'=>'Marketplace homepage',
   'meta_description'=>'Shop curated marketplace assets.',
   'status'=>'published',
   'settings'=>[
    'hero_badge_text'=>'Marketplace picks',
    'cta_primary_text'=>'Browse Assets',
    'cta_primary_url'=>'/products?sort=popular',
    'cta_secondary_text'=>'Sell Your Work',
    'cta_secondary_url'=>'/register',
    'show_categories'=>'1',
    'categories_title'=>'Shop by category',
    'categories_subtitle'=>'Start with the asset type you need most.',
    'show_trending'=>'1',
    'trending_title'=>'Hot this week',
    'trending_subtitle'=>'Products buyers are loving right now.',
    'show_new_arrivals'=>'1',
    'new_arrivals_title'=>'Just released',
    'new_arrivals_subtitle'=>'Fresh drops from active sellers.',
    'show_featured_creators'=>'1',
    'featured_creators_title'=>'Top creators',
    'featured_creators_subtitle'=>'Studios building standout marketplace products.',
   ],
  ];
  $this->actingAs($admin)->put('/admin/pages/homepage',$payload)->assertRedirect('/admin/pages');
  $this->assertDatabaseHas('pages',['slug'=>Page::HOMEPAGE_SLUG,'title'=>'Digital products for fast-moving teams.','meta_title'=>'Marketplace homepage']);
  $this->get('/')->assertOk()->assertSee('Digital products for fast-moving teams.')->assertSee('Launch client projects with curated scripts, UI kits, and marketplace-ready tools.')->assertSee('Marketplace picks')->assertSee('Browse Assets')->assertSee('/products?sort=popular',false)->assertSee('Homepage custom content block.')->assertSee('Shop by category')->assertSee('Hot this week')->assertSee('Just released');
  $this->get('/pages/home')->assertNotFound();
  $this->get('/sitemap.xml')->assertOk()->assertDontSee('/pages/home');
 }
}
