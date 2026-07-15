<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class AdminCategoryTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User
 {
  $admin=User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  return $admin;
 }
 public function test_admin_creates_category_with_image_and_it_renders_on_homepage(): void
 {
  Storage::fake('public');
  $this->actingAs($this->admin())->post('/admin/categories',['name'=>'AI Tools','icon'=>'smart_toy','description'=>'Smart software.','display_order'=>'1','is_active'=>'1','image'=>UploadedFile::fake()->image('ai.png',800,500)])->assertRedirect()->assertSessionHas('status');
  $category=Category::where('slug','ai-tools')->firstOrFail();
  $this->assertNotNull($category->image_path);
  Storage::disk('public')->assertExists($category->image_path);
  $this->get('/')->assertOk()->assertSee($category->image_path);
  $this->get('/categories/ai-tools')->assertOk()->assertSee('AI Tools');
 }
 public function test_slug_is_generated_uniquely(): void
 {
  $admin=$this->admin();
  $this->actingAs($admin)->post('/admin/categories',['name'=>'Widgets']);
  $this->actingAs($admin)->post('/admin/categories',['name'=>'Widgets']);
  $this->assertSame(1,Category::where('slug','widgets')->count());
  $this->assertSame(1,Category::where('slug','widgets-2')->count());
 }
 public function test_toggle_hides_category_from_homepage(): void
 {
  $category=Category::create(['name'=>'Hidden Cat','slug'=>'hidden-cat','is_active'=>true]);
  $this->actingAs($this->admin())->put("/admin/categories/{$category->id}/toggle")->assertRedirect();
  $this->assertFalse($category->fresh()->is_active);
  // Assert against the category link (the flash banner echoes the name, so name alone is ambiguous).
  $this->get('/')->assertOk()->assertDontSee(route('categories.show','hidden-cat'));
 }
 public function test_category_with_products_cannot_be_deleted(): void
 {
  $seller=User::factory()->create();
  $category=Category::create(['name'=>'Busy','slug'=>'busy','is_active'=>true]);
  Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'P','slug'=>'busy-p','short_description'=>'s','description'=>str_repeat('x ',30),'regular_price'=>'5.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $this->actingAs($this->admin())->delete("/admin/categories/{$category->id}")->assertStatus(422);
  $this->assertDatabaseHas('categories',['id'=>$category->id]);
 }
 public function test_non_admin_cannot_manage_categories(): void
 {
  $this->actingAs(User::factory()->create())->post('/admin/categories',['name'=>'Nope'])->assertForbidden();
 }
 public function test_image_can_be_removed(): void
 {
  Storage::fake('public');
  $admin=$this->admin();
  $this->actingAs($admin)->post('/admin/categories',['name'=>'Pics','image'=>UploadedFile::fake()->image('p.png')]);
  $category=Category::where('slug','pics')->firstOrFail();
  $path=$category->image_path;
  $this->actingAs($admin)->delete("/admin/categories/{$category->id}/image")->assertRedirect();
  Storage::disk('public')->assertMissing($path);
  $this->assertNull($category->fresh()->image_path);
 }
}
