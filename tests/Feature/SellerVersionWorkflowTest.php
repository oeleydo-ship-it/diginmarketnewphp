<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class SellerVersionWorkflowTest extends TestCase
{
 use RefreshDatabase;
 private function role(string $slug): Role { return Role::firstOrCreate(['slug'=>$slug],['name'=>str($slug)->headline()]); }
 private function publishedProduct(): array
 {
  Storage::fake('local');
  $seller=User::factory()->create();$seller->roles()->attach($this->role('seller'));
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Version Studio','username'=>'version-studio','country'=>'AE','biography'=>'Seller','status'=>'approved']);
  $category=Category::create(['name'=>'Version Apps','slug'=>'version-apps']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Deploy Kit','slug'=>'deploy-kit','short_description'=>'Deploys.','description'=>str_repeat('Complete deploy toolkit. ',4),'regular_price'=>'50.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $product->versions()->create(['version_number'=>'1.0.0','release_title'=>'Stable','status'=>ProductVersionStatus::Published,'published_at'=>now()]);
  return compact('seller','category','product');
 }
 public function test_new_version_for_published_product_awaits_review(): void
 {
  // Details edits on published products now resubmit for review (PublishedListingEditTest);
  // this test covers the version-update queue.
  $d=$this->publishedProduct();
  $this->actingAs($d['seller'])->post('/seller/products/'.$d['product']->id.'/versions',['version_number'=>'1.1.0','release_title'=>'Bugfix release','archive'=>UploadedFile::fake()->create('update.zip',10,'application/zip')])->assertRedirect();
  $this->assertDatabaseHas('product_versions',['product_id'=>$d['product']->id,'version_number'=>'1.1.0','status'=>'pending_review']);
  $this->actingAs($d['seller'])->post('/seller/products/'.$d['product']->id.'/versions',['version_number'=>'1.2.0','release_title'=>'Too soon','archive'=>UploadedFile::fake()->create('again.zip',10,'application/zip')])->assertSessionHasErrors('version_number');
 }
 public function test_draft_product_can_be_edited_by_owner_only(): void
 {
  $d=$this->publishedProduct();
  $draft=Product::create(['seller_id'=>$d['seller']->id,'category_id'=>$d['category']->id,'title'=>'Draft Kit','slug'=>'draft-kit','short_description'=>'Draft.','description'=>str_repeat('Draft description text. ',4),'regular_price'=>'10.00','status'=>ProductStatus::Draft]);
  $this->actingAs($d['seller'])->put('/seller/products/'.$draft->id,['category_id'=>$d['category']->id,'title'=>'Draft Kit Pro','short_description'=>'Draft.','description'=>str_repeat('Draft description text. ',4),'regular_price'=>'12.00'])->assertRedirect();
  $this->assertDatabaseHas('products',['id'=>$draft->id,'title'=>'Draft Kit Pro']);
  $other=User::factory()->create();$other->roles()->attach($this->role('seller'));
  SellerProfile::create(['user_id'=>$other->id,'display_name'=>'Other','username'=>'other-studio','country'=>'AE','biography'=>'Seller','status'=>'approved']);
  $this->actingAs($other)->put('/seller/products/'.$draft->id,['category_id'=>$d['category']->id,'title'=>'Hijack','short_description'=>'x','description'=>str_repeat('Hijack attempt text. ',4),'regular_price'=>'1.00'])->assertForbidden();
 }
 public function test_admin_approves_version_and_it_publishes_with_audit(): void
 {
  $d=$this->publishedProduct();
  $version=$d['product']->versions()->create(['version_number'=>'2.0.0','release_title'=>'Major','status'=>ProductVersionStatus::PendingReview]);
  $admin=User::factory()->create();$admin->roles()->attach($this->role('administrator'));
  $this->actingAs($admin)->post('/admin/versions/'.$version->id.'/approve',['notes'=>'Looks good'])->assertRedirect();
  $version->refresh();
  $this->assertSame(ProductVersionStatus::Published,$version->status);
  $this->assertNotNull($version->published_at);
  $this->assertDatabaseHas('audit_logs',['action'=>'version.approved','entity_id'=>$version->id]);
 }
 public function test_admin_rejects_version_with_reason(): void
 {
  $d=$this->publishedProduct();
  $version=$d['product']->versions()->create(['version_number'=>'2.0.0','release_title'=>'Major','status'=>ProductVersionStatus::PendingReview]);
  $admin=User::factory()->create();$admin->roles()->attach($this->role('administrator'));
  $this->actingAs($admin)->post('/admin/versions/'.$version->id.'/reject',['notes'=>'Archive fails to extract.'])->assertRedirect();
  $this->assertSame(ProductVersionStatus::Rejected,$version->fresh()->status);
  $this->assertDatabaseHas('audit_logs',['action'=>'version.rejected','entity_id'=>$version->id]);
 }
 public function test_seller_cannot_approve_own_version(): void
 {
  $d=$this->publishedProduct();
  $version=$d['product']->versions()->create(['version_number'=>'2.0.0','release_title'=>'Major','status'=>ProductVersionStatus::PendingReview]);
  $d['seller']->roles()->attach($this->role('administrator'));
  $this->actingAs($d['seller'])->post('/admin/versions/'.$version->id.'/approve')->assertForbidden();
 }
}
