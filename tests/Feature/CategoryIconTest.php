<?php
namespace Tests\Feature;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CategoryIconTest extends TestCase
{
 use RefreshDatabase;

 public function test_icons_are_derived_from_category_names(): void
 {
  $cases = [
   ['WordPress Themes', 'view_quilt'],
   ['JavaScript Applications', 'javascript'],
   ['PHP Scripts', 'php'],
   ['Mobile Applications', 'smartphone'],
   ['AI Tools', 'smart_toy'],
   ['UI Templates', 'palette'],
   ['Video Editing Tools', 'movie'],
   ['E-Commerce Plugins', 'extension'],
   ['Something Nobody Expected', 'category'],
  ];
  foreach ($cases as [$name, $expected]) {
   $category = Category::create(['name' => $name, 'slug' => str($name)->slug()]);
   $this->assertSame($expected, $category->displayIcon(), $name.' should resolve to '.$expected);
  }
 }

 public function test_admin_chosen_icon_always_wins(): void
 {
  $category = Category::create(['name' => 'WordPress Themes', 'slug' => 'wp-x', 'icon' => 'rocket_launch']);
  $this->assertSame('rocket_launch', $category->displayIcon());
 }

 public function test_homepage_tiles_render_the_derived_icon(): void
 {
  Category::create(['name' => 'Video Courses', 'slug' => 'video-courses', 'is_active' => true]);
  Category::create(['name' => 'Security Plugins', 'slug' => 'security-plugins', 'is_active' => true]);
  $this->get('/')->assertOk()->assertSee('movie')->assertSee('security');
 }
}
