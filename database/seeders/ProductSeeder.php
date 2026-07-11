<?php
namespace Database\Seeders;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
class ProductSeeder extends Seeder
{
 public function run(): void
 {
  $sellerRole=Role::where('slug','seller')->firstOrFail();
  $seller=User::firstOrCreate(['email'=>'seller@example.com'],['name'=>'Nova Digital Studio','password'=>bcrypt('password'),'email_verified_at'=>now()]);
  $seller->roles()->syncWithoutDetaching([$sellerRole->id]);
  SellerProfile::updateOrCreate(['user_id'=>$seller->id],['display_name'=>'Nova Digital Studio','username'=>'nova-digital','country'=>'AE','biography'=>'Premium scripts, themes and app templates crafted since 2019.','status'=>SellerStatus::Approved,'reviewed_at'=>now()]);
  $categories=Category::pluck('id','slug');
  $products=[
   ['category'=>'php-scripts','title'=>'InvoiceFlow – PHP Invoicing & Billing System','price'=>39,'extended'=>199,'featured'=>true,'trending'=>true,'sales'=>842,'rating'=>4.7,'short'=>'Complete invoicing, estimates and recurring billing platform with client portal and PDF export.'],
   ['category'=>'php-scripts','title'=>'LinkVault – URL Shortener with Analytics','price'=>19,'extended'=>99,'featured'=>false,'trending'=>true,'sales'=>567,'rating'=>4.4,'short'=>'Branded short links, QR codes, click analytics and team workspaces in a lightweight PHP app.'],
   ['category'=>'laravel-applications','title'=>'HelpDesk Pro – Laravel Support Ticket System','price'=>59,'extended'=>299,'featured'=>true,'trending'=>false,'sales'=>1204,'rating'=>4.8,'short'=>'Multi-department ticketing with SLA timers, canned replies, knowledge base and REST API.'],
   ['category'=>'laravel-applications','title'=>'Stocked – Laravel Inventory & POS','price'=>49,'extended'=>249,'featured'=>false,'trending'=>true,'sales'=>431,'rating'=>4.5,'short'=>'Barcode-ready inventory, purchase orders, multi-warehouse stock and a touch-friendly POS.'],
   ['category'=>'wordpress-themes','title'=>'Aurelia – Creative Portfolio WordPress Theme','price'=>29,'extended'=>149,'featured'=>true,'trending'=>false,'sales'=>2310,'rating'=>4.6,'short'=>'Elementor-ready portfolio theme with 24 demos, WooCommerce support and one-click import.'],
   ['category'=>'wordpress-themes','title'=>'Bistrova – Restaurant & Cafe Theme','price'=>24,'extended'=>129,'featured'=>false,'trending'=>false,'sales'=>318,'rating'=>4.2,'short'=>'Menus, table reservations and delivery integration for restaurants, cafes and food trucks.'],
   ['category'=>'javascript-applications','title'=>'ChartDeck – React Analytics Dashboard','price'=>34,'extended'=>179,'featured'=>true,'trending'=>true,'sales'=>956,'rating'=>4.9,'short'=>'80+ dashboard screens, dark mode, RTL and TypeScript across React, Next.js and Vite builds.'],
   ['category'=>'javascript-applications','title'=>'Socketly – Node.js Real-Time Chat Platform','price'=>44,'extended'=>219,'featured'=>false,'trending'=>false,'sales'=>274,'rating'=>4.3,'short'=>'Channels, DMs, file sharing and presence built on Node.js, Socket.IO and MongoDB.'],
   ['category'=>'mobile-applications','title'=>'FitTrackr – Flutter Fitness App Template','price'=>39,'extended'=>199,'featured'=>false,'trending'=>true,'sales'=>689,'rating'=>4.5,'short'=>'Workout plans, calorie tracking and wearable sync in a Flutter template with Firebase backend.'],
   ['category'=>'mobile-applications','title'=>'ShopSwift – React Native E-Commerce App','price'=>49,'extended'=>249,'featured'=>true,'trending'=>false,'sales'=>512,'rating'=>4.6,'short'=>'Full storefront app with cart, Stripe checkout, order tracking and push notifications.'],
   ['category'=>'ui-templates','title'=>'Lumina UI – Multipurpose Landing Page Kit','price'=>18,'extended'=>89,'featured'=>false,'trending'=>true,'sales'=>1745,'rating'=>4.7,'short'=>'120 responsive Tailwind CSS sections and 15 complete landing pages with Figma source.'],
   ['category'=>'ui-templates','title'=>'AdminX – Bootstrap 5 Admin Template','price'=>22,'extended'=>109,'featured'=>false,'trending'=>false,'sales'=>398,'rating'=>4.1,'short'=>'Clean Bootstrap 5 admin with 60 pages, charts, form wizards and six colour schemes.'],
  ];
  foreach($products as $index=>$data){
   $slug=str($data['title'])->slug();
   $publishedAt=now()->subDays(count($products)-$index)->subHours($index*3);
   $product=Product::updateOrCreate(['slug'=>$slug],[
    'seller_id'=>$seller->id,
    'category_id'=>$categories[$data['category']],
    'title'=>$data['title'],
    'short_description'=>$data['short'],
    'description'=>$data['short']." Built for production use with clean, documented code.\n\nHighlights:\n- Six months of support and free lifetime updates\n- Detailed documentation and quick-start guide\n- Easy configuration with sensible defaults\n- Regular releases driven by customer feedback",
    'regular_price'=>$data['price'],
    'extended_price'=>$data['extended'],
    'status'=>ProductStatus::Published,
    'submitted_at'=>$publishedAt->copy()->subDays(3),
    'published_at'=>$publishedAt,
    'views_count'=>$data['sales']*17,
    'sales_count'=>$data['sales'],
    'average_rating'=>$data['rating'],
    'is_featured'=>$data['featured'],
    'is_trending'=>$data['trending'],
    'seo_title'=>$data['title'],
    'seo_description'=>$data['short'],
   ]);
   $product->versions()->updateOrCreate(['version_number'=>'1.0.0'],['release_title'=>'Initial release','release_notes'=>'First public release.','status'=>ProductVersionStatus::Published,'published_at'=>$publishedAt]);
  }
 }
}
