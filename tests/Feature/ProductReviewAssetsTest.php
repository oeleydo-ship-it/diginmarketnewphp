<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ProductReviewAssetsTest extends TestCase
{
 use RefreshDatabase;
 private function role(string $slug): Role { return Role::firstOrCreate(['slug'=>$slug],['name'=>str($slug)->headline()]); }
 private function admin(): User { $admin=User::factory()->create();$admin->roles()->attach($this->role('administrator'));return $admin; }
 private function submittedProduct(array $overrides=[]): array
 {
  Storage::fake('local');Storage::fake('public');
  $seller=User::factory()->create(['name'=>'DiginMarket Super Admin']);
  $seller->roles()->attach($this->role('seller'));
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Uplary Studio','username'=>'uplary-studio-'.$seller->id,'country'=>'AE','biography'=>'Approved seller','status'=>SellerStatus::Approved]);
  $category=Category::firstOrCreate(['slug'=>'review-apps'],['name'=>'Review Apps']);
  $this->actingAs($seller)->post('/seller/products',[
   'category_id'=>$category->id,
   'title'=>'uplary-saas management software',
   'short_description'=>'SaaS management for teams.',
   'description'=>str_repeat('Full-featured saas management software details. ',3),
   'regular_price'=>'49.00',
   'demo_url'=>'https://demo.uplary.test/app',
   'version_number'=>'1.0.0',
   'release_title'=>'Initial release',
   'archive'=>UploadedFile::fake()->create('uplary.zip',20,'application/zip'),
   'images'=>[UploadedFile::fake()->image('cover.png',640,360)],
  ]+$overrides)->assertRedirect('/seller/products');
  $product=Product::firstOrFail();
  $this->actingAs($seller)->post("/seller/products/{$product->id}/submit")->assertRedirect();
  return compact('seller','product');
 }
 public function test_review_queue_shows_demo_url_and_admin_preview_without_public_listing(): void
 {
  $data=$this->submittedProduct();
  $admin=$this->admin();
  $this->actingAs($admin)->get('/admin/products/review')->assertOk()
   ->assertSee('https://demo.uplary.test/app',false)
   ->assertSee('Live preview')
   ->assertSee('Download zip')
   ->assertSee('Admin preview')
   ->assertSee('Public listing appears after approve')
   ->assertDontSee(route('products.show',$data['product']->slug),false);
  $this->actingAs($admin)->get("/admin/products/{$data['product']->id}/review")->assertOk()
   ->assertSee('uplary-saas management software')
   ->assertSee('https://demo.uplary.test/app',false)
   ->assertSee('Download zip');
  $this->get("/products/{$data['product']->slug}")->assertNotFound();
 }
 public function test_admin_can_download_submitted_zip_from_private_disk(): void
 {
  $data=$this->submittedProduct();
  $file=$data['product']->reviewVersion()->downloadableFile();
  $this->assertNotNull($file);
  $this->assertSame('local',$file->disk);
  Storage::disk('local')->put($file->path,'private archive');
  $response=$this->actingAs($this->admin())->get("/admin/products/{$data['product']->id}/archive");
  $response->assertOk();
  $this->assertStringContainsString('attachment',$response->headers->get('content-disposition'));
  $this->assertStringContainsString('uplary.zip',$response->headers->get('content-disposition'));
  $this->assertStringNotContainsString('/storage/',$response->headers->get('content-disposition') ?? '');
  $this->assertDatabaseHas('audit_logs',['action'=>'product.archive_downloaded','entity_id'=>$data['product']->id]);
 }
 public function test_pending_version_zip_is_downloadable_for_admins(): void
 {
  Storage::fake('local');
  $seller=User::factory()->create();$seller->roles()->attach($this->role('seller'));
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Version Studio','username'=>'version-review-'.$seller->id,'country'=>'AE','biography'=>'Seller','status'=>SellerStatus::Approved]);
  $category=Category::create(['name'=>'Version Apps','slug'=>'version-review-apps']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Deploy Kit','slug'=>'deploy-kit-review','short_description'=>'Deploys.','description'=>str_repeat('Complete deploy toolkit. ',4),'regular_price'=>'50.00','demo_url'=>'https://demo.deploy.test','status'=>ProductStatus::Published,'published_at'=>now()]);
  $product->versions()->create(['version_number'=>'1.0.0','release_title'=>'Stable','status'=>ProductVersionStatus::Published,'published_at'=>now()]);
  $this->actingAs($seller)->post('/seller/products/'.$product->id.'/versions',['version_number'=>'1.1.0','release_title'=>'Bugfix release','archive'=>UploadedFile::fake()->create('update.zip',10,'application/zip')])->assertRedirect();
  $version=$product->versions()->where('version_number','1.1.0')->firstOrFail();
  $file=$version->downloadableFile();
  $this->assertNotNull($file);
  Storage::disk('local')->put($file->path,'version archive');
  $admin=$this->admin();
  $this->actingAs($admin)->get('/admin/products/review')->assertOk()->assertSee('Download zip')->assertSee('https://demo.deploy.test',false)->assertSee(route('products.show',$product->slug),false);
  $this->actingAs($admin)->get("/admin/versions/{$version->id}/archive")->assertOk();
  $this->assertStringContainsString('update.zip',$this->actingAs($admin)->get("/admin/versions/{$version->id}/archive")->headers->get('content-disposition'));
 }
 public function test_non_admin_and_guest_cannot_download_review_zip(): void
 {
  $data=$this->submittedProduct();
  $customer=User::factory()->create();$customer->roles()->attach($this->role('customer'));
  $this->actingAs($customer)->get("/admin/products/{$data['product']->id}/archive")->assertForbidden();
  $this->actingAs($data['seller'])->get("/admin/products/{$data['product']->id}/archive")->assertForbidden();
  $this->post('/logout');
  $this->get("/admin/products/{$data['product']->id}/archive")->assertRedirect('/login');
 }
 public function test_administrator_who_owns_the_submitted_product_can_approve_it(): void
 {
  $data=$this->submittedProduct();
  $data['seller']->roles()->attach($this->role('administrator'));
  $this->actingAs($data['seller'])->post("/admin/products/{$data['product']->id}/approve",['notes'=>'Looks good'])->assertRedirect();
  $this->assertSame(ProductStatus::Published,$data['product']->fresh()->status);
 }
 public function test_seller_without_administrator_cannot_approve_own_product(): void
 {
  $data=$this->submittedProduct();
  $this->actingAs($data['seller'])->post("/admin/products/{$data['product']->id}/approve")->assertForbidden();
 }
 public function test_get_approve_url_redirects_admins_to_the_review_queue(): void
 {
  $data=$this->submittedProduct();
  $this->actingAs($this->admin())->get("/admin/products/{$data['product']->id}/approve")->assertRedirect('/admin/products/review');
 }
}
