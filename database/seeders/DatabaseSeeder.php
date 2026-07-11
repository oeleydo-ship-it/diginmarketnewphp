<?php
namespace Database\Seeders;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
 public function run(): void
 {
  $roles=collect([['name'=>'Administrator','slug'=>'administrator','description'=>'Full marketplace operations'],['name'=>'Seller','slug'=>'seller','description'=>'Product publishing and sales'],['name'=>'Customer','slug'=>'customer','description'=>'Purchasing and licensing']])->mapWithKeys(fn(array $role)=>[$role['slug']=>Role::updateOrCreate(['slug'=>$role['slug']],$role)]);
  $admin=User::factory()->create(['name'=>'Marketplace Admin','email'=>'admin@example.com']);$admin->roles()->sync([$roles['administrator']->id]);
  Setting::updateOrCreate(['key'=>'marketplace.name'],['group'=>'general','value'=>'DiginMarket','is_public'=>true]);Setting::updateOrCreate(['key'=>'marketplace.currency'],['group'=>'marketplace','value'=>'USD','is_public'=>true]);Setting::updateOrCreate(['key'=>'commission.default_rate'],['group'=>'commissions','value'=>'20.00','type'=>'decimal']);
  foreach(['PHP Scripts','Laravel Applications','WordPress Themes','JavaScript Applications','Mobile Applications','UI Templates'] as $index=>$name)Category::updateOrCreate(['slug'=>str($name)->slug()],['name'=>$name,'display_order'=>$index]);
  LicenseType::updateOrCreate(['slug'=>'regular'],['name'=>'Regular License','description'=>'Use in one end product where end users are not charged.','allows_paid_end_product'=>false]);LicenseType::updateOrCreate(['slug'=>'extended'],['name'=>'Extended License','description'=>'Use in one end product where end users may be charged.','allows_paid_end_product'=>true]);
  $this->call(ProductSeeder::class);
 }
}