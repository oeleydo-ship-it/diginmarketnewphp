<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
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
class PhaseTwoWorkflowTest extends TestCase
{
 use RefreshDatabase;
 private function role(string $slug): Role { return Role::create(['name'=>str($slug)->headline(),'slug'=>$slug]); }
 public function test_seller_application_can_be_approved_by_an_administrator(): void
 {
  $customer=$this->role('customer');$sellerRole=$this->role('seller');$adminRole=$this->role('administrator');
  $applicant=User::factory()->create();$applicant->roles()->attach($customer);
  $this->actingAs($applicant)->post('/sell/apply',['display_name'=>'Acme Studio','username'=>'acme-studio','country'=>'AE','biography'=>'We build production-ready Laravel applications.'])->assertSessionHasErrors(['full_name','address','city','business_name']);
  $this->actingAs($applicant)->post('/sell/apply',['full_name'=>'Alice Acme','display_name'=>'Acme Studio','username'=>'acme-studio','country'=>'AE','address'=>'12 Harbour Road','city'=>'Dubai','postal_code'=>'00000','business_name'=>'Acme Digital FZ-LLC','biography'=>'We build production-ready Laravel applications.'])->assertRedirect('/dashboard');
  $profile=$applicant->sellerProfile()->firstOrFail();$this->assertSame(SellerStatus::Pending,$profile->status);
  $admin=User::factory()->create();$admin->roles()->attach($adminRole);
  $this->actingAs($admin)->post("/admin/sellers/{$profile->id}/approve")->assertRedirect();
  $this->assertSame(SellerStatus::Approved,$profile->fresh()->status);$this->assertTrue($applicant->hasRole('seller'));
 }
 public function test_apply_page_redirects_users_who_already_applied(): void
 {
  $this->role('seller');
  $pending=User::factory()->create();SellerProfile::create(['user_id'=>$pending->id,'display_name'=>'Waiting Studio','username'=>'waiting-studio','country'=>'AE','biography'=>'Pending applicant','status'=>SellerStatus::Pending]);
  $this->actingAs($pending)->get('/sell/apply')->assertRedirect('/dashboard')->assertSessionHas('status');
  $approved=User::factory()->create();$approved->roles()->attach(Role::where('slug','seller')->firstOrFail());SellerProfile::create(['user_id'=>$approved->id,'display_name'=>'Live Studio','username'=>'live-studio','country'=>'AE','biography'=>'Approved seller','status'=>SellerStatus::Approved]);
  $this->actingAs($approved)->get('/sell/apply')->assertRedirect('/seller/products');
 }
 public function test_private_product_can_be_submitted_reviewed_and_published(): void
 {
  Storage::fake('local');$sellerRole=$this->role('seller');$adminRole=$this->role('administrator');$category=Category::create(['name'=>'Laravel Applications','slug'=>'laravel-applications']);
  $seller=User::factory()->create();$seller->roles()->attach($sellerRole);SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Studio','username'=>'studio','country'=>'AE','biography'=>'Experienced seller','status'=>SellerStatus::Approved]);
  $this->actingAs($seller)->post('/seller/products',['category_id'=>$category->id,'title'=>'Invoice Manager','short_description'=>'A complete invoice management application.','description'=>str_repeat('Production-ready invoicing features and documentation. ',3),'regular_price'=>'49.00','extended_price'=>'249.00','version_number'=>'1.0.0','release_title'=>'Initial release','release_notes'=>'First stable version.','archive'=>UploadedFile::fake()->create('invoice-manager.zip',20,'application/zip')])->assertRedirect('/seller/products');
  $product=Product::firstOrFail();$this->get("/products/{$product->slug}")->assertNotFound();
  $this->actingAs($seller)->post("/seller/products/{$product->id}/submit")->assertRedirect();$this->assertSame(ProductStatus::Submitted,$product->fresh()->status);
  $admin=User::factory()->create();$admin->roles()->attach($adminRole);
  $this->actingAs($admin)->post("/admin/products/{$product->id}/approve",['notes'=>'Reviewed and accepted.'])->assertRedirect();
  $this->assertSame(ProductStatus::Published,$product->fresh()->status);$this->get("/products/{$product->slug}")->assertOk();
 }
 public function test_seller_cannot_approve_own_product(): void
 {
  $sellerRole=$this->role('seller');$seller=User::factory()->create();$seller->roles()->attach($sellerRole);SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Studio','username'=>'self-review','country'=>'AE','biography'=>'Seller','status'=>SellerStatus::Approved]);$category=Category::create(['name'=>'Scripts','slug'=>'scripts']);$product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Tool','slug'=>'tool','short_description'=>'Useful tool','description'=>str_repeat('Detailed description ',5),'regular_price'=>'10.00','status'=>ProductStatus::Submitted]);
  $this->actingAs($seller)->post("/admin/products/{$product->id}/approve")->assertForbidden();
 }
}