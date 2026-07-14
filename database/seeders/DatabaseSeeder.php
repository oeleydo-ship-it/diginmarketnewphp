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
  $this->call(SuperAdminSeeder::class);
  Setting::updateOrCreate(['key'=>'marketplace.name'],['group'=>'general','value'=>'DiginMarket','is_public'=>true]);Setting::updateOrCreate(['key'=>'marketplace.currency'],['group'=>'marketplace','value'=>'USD','is_public'=>true]);Setting::updateOrCreate(['key'=>'commission.default_rate'],['group'=>'commissions','value'=>'20.00','type'=>'decimal']);
  foreach(['PHP Scripts','Laravel Applications','WordPress Themes','JavaScript Applications','Mobile Applications','UI Templates'] as $index=>$name)Category::updateOrCreate(['slug'=>str($name)->slug()],['name'=>$name,'display_order'=>$index]);
  LicenseType::updateOrCreate(['slug'=>'regular'],['name'=>'Regular License','description'=>'Use in one end product where end users are not charged.','allows_paid_end_product'=>false]);LicenseType::updateOrCreate(['slug'=>'extended'],['name'=>'Business License','description'=>'Use in one end product where end users may be charged.','allows_paid_end_product'=>true]);
  foreach([['footer-legal','Privacy Policy','/pages/privacy-policy',0],['footer-legal','Terms of Service','/pages/terms-of-service',1],['footer-legal','Seller Agreement','/pages/seller-agreement',2],['footer-resources','Blog','/blog',0]] as [$location,$label,$url,$order])\App\Models\MenuItem::updateOrCreate(['location'=>$location,'label'=>$label],['url'=>$url,'display_order'=>$order,'is_active'=>true]);
  $this->call(ProductSeeder::class);
 }
}
