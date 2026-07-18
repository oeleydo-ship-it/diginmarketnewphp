<?php
namespace Tests\Feature;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HeaderMenuTest extends TestCase
{
 use RefreshDatabase;

 private function admin(): User
 {
  $admin = User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  return $admin;
 }

 public function test_header_shows_defaults_until_custom_items_exist(): void
 {
  $this->get('/')->assertOk()->assertSee('Browse')->assertSee('Bundles');
 }

 public function test_admin_defined_header_items_replace_the_defaults(): void
 {
  $admin = $this->admin();
  $this->actingAs($admin)->post(route('admin.menus.store'), ['location' => 'header', 'label' => 'Deals', 'url' => '/bundles', 'display_order' => 1])->assertRedirect();
  $this->post(route('admin.menus.store'), ['location' => 'header', 'label' => 'Docs', 'url' => 'https://docs.example.com', 'display_order' => 2])->assertRedirect();
  $response = $this->get('/');
  $response->assertOk()->assertSee('Deals')->assertSee('Docs')
   ->assertDontSee('>Browse<', false); // defaults replaced, not appended
  // Menus page offers the header location.
  $this->get('/admin/menus')->assertOk()->assertSee('Header navigation');
 }

 public function test_deleting_all_header_items_restores_defaults(): void
 {
  $admin = $this->admin();
  $this->actingAs($admin)->post(route('admin.menus.store'), ['location' => 'header', 'label' => 'Deals', 'url' => '/bundles'])->assertRedirect();
  $this->get('/')->assertSee('Deals');
  $item = MenuItem::where('location', 'header')->firstOrFail();
  $this->delete(route('admin.menus.destroy', $item))->assertRedirect();
  $this->get('/')->assertOk()->assertSee('Browse')->assertDontSee('Deals');
 }

 public function test_inactive_header_items_are_hidden(): void
 {
  $this->actingAs($this->admin())->post(route('admin.menus.store'), ['location' => 'header', 'label' => 'Visible', 'url' => '/bundles'])->assertRedirect();
  MenuItem::create(['location' => 'header', 'label' => 'Hidden Draft', 'url' => '/x', 'display_order' => 5, 'is_active' => false]);
  MenuItem::bustCache();
  $this->get('/')->assertOk()->assertSee('Visible')->assertDontSee('Hidden Draft');
 }
}
