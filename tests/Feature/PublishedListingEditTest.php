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
use Tests\TestCase;
class PublishedListingEditTest extends TestCase
{
 use RefreshDatabase;
 private function publishedProduct(): array
 {
  $sellerRole=Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']);
  $seller=User::factory()->create();$seller->roles()->attach($sellerRole);
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Edit Studio','username'=>'edit-studio-'.$seller->id,'country'=>'AE','biography'=>'Approved seller','status'=>SellerStatus::Approved]);
  $category=Category::firstOrCreate(['slug'=>'edit-apps'],['name'=>'Edit Apps']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Live App','slug'=>'live-app-'.$seller->id,'short_description'=>'A live app.','description'=>str_repeat('Detailed live application description. ',3),'regular_price'=>'40.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  return compact('seller','product');
 }
 private function payload(Product $product,string $title): array
 {
  return ['category_id'=>$product->category_id,'title'=>$title,'short_description'=>$product->short_description,'description'=>$product->description,'regular_price'=>(string)$product->regular_price];
 }
 public function test_edit_page_locks_published_listing_behind_unlock_button(): void
 {
  $data=$this->publishedProduct();
  $this->actingAs($data['seller'])->get("/seller/products/{$data['product']->id}/edit")->assertOk()->assertSee('Unlock &amp; edit',false)->assertDontSee('Save changes');
  $this->actingAs($data['seller'])->get("/seller/products/{$data['product']->id}/edit?unlock=1")->assertOk()->assertSee('Save &amp; submit for review',false)->assertSee('editing a live listing');
 }
 public function test_saving_published_listing_resubmits_it_for_review_and_hides_it(): void
 {
  $data=$this->publishedProduct();$product=$data['product'];
  $this->get("/products/{$product->slug}")->assertOk();
  $this->actingAs($data['seller'])->put("/seller/products/{$product->id}",$this->payload($product,'Live App Renamed'))->assertRedirect(route('seller.products.edit',$product));
  $product->refresh();
  $this->assertSame('Live App Renamed',$product->title);
  $this->assertSame(ProductStatus::Submitted,$product->status);
  $this->assertDatabaseHas('product_review_submissions',['product_id'=>$product->id,'status'=>'submitted','submitted_by'=>$data['seller']->id]);
  $this->get("/products/{$product->slug}")->assertNotFound();
  $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $this->actingAs($admin)->post("/admin/products/{$product->id}/approve",['notes'=>'Looks good.'])->assertRedirect();
  $this->assertSame(ProductStatus::Published,$product->fresh()->status);
  $this->get("/products/{$product->slug}")->assertOk()->assertSee('Live App Renamed');
 }
 public function test_listing_under_review_cannot_be_edited(): void
 {
  $data=$this->publishedProduct();$product=$data['product'];
  $product->update(['status'=>ProductStatus::Submitted]);
  $this->actingAs($data['seller'])->put("/seller/products/{$product->id}",$this->payload($product,'Sneaky Rename'))->assertForbidden();
  $this->actingAs($data['seller'])->get("/seller/products/{$product->id}/edit")->assertOk()->assertSee('awaiting review');
 }
 public function test_draft_edits_do_not_trigger_review(): void
 {
  $data=$this->publishedProduct();$product=$data['product'];
  $product->update(['status'=>ProductStatus::Draft,'published_at'=>null]);
  $this->actingAs($data['seller'])->put("/seller/products/{$product->id}",$this->payload($product,'Draft Rename'))->assertRedirect();
  $this->assertSame(ProductStatus::Draft,$product->fresh()->status);
  $this->assertDatabaseCount('product_review_submissions',0);
 }
}
