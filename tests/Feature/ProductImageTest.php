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
class ProductImageTest extends TestCase
{
 use RefreshDatabase;
 private function seller(): User
 {
  $role=Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']);
  $seller=User::factory()->create();$seller->roles()->attach($role);
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Image Studio','username'=>'image-studio-'.$seller->id,'country'=>'AE','biography'=>'Approved seller','status'=>SellerStatus::Approved]);
  return $seller;
 }
 private function payload(): array
 {
  $category=Category::firstOrCreate(['slug'=>'image-apps'],['name'=>'Image Apps']);
  return ['category_id'=>$category->id,'title'=>'Gallery App','short_description'=>'An app with screenshots.','description'=>str_repeat('Feature-rich gallery application details. ',3),'regular_price'=>'25.00','version_number'=>'1.0.0','release_title'=>'Initial release','archive'=>UploadedFile::fake()->create('gallery.zip',10,'application/zip')];
 }
 public function test_seller_uploads_images_with_new_product_and_cover_is_set(): void
 {
  Storage::fake('local');Storage::fake('public');
  $this->actingAs($this->seller())->post('/seller/products',$this->payload()+['images'=>[UploadedFile::fake()->image('shot1.png',1280,720),UploadedFile::fake()->image('shot2.jpg',1280,720)]])->assertRedirect('/seller/products');
  $product=Product::firstOrFail();
  $this->assertSame(2,$product->images()->count());
  $this->assertNotNull($product->cover_image_path);
  $this->assertSame($product->images()->orderBy('sort_order')->value('path'),$product->cover_image_path);
  foreach($product->images as $image)Storage::disk('public')->assertExists($image->path);
 }
 public function test_non_image_files_are_rejected(): void
 {
  Storage::fake('local');Storage::fake('public');
  $this->actingAs($this->seller())->post('/seller/products',$this->payload()+['images'=>[UploadedFile::fake()->create('doc.pdf',100,'application/pdf')]])->assertSessionHasErrors('images.0');
  $this->assertDatabaseCount('products',0);
 }
 public function test_images_can_be_added_and_removed_after_publish_and_cover_follows(): void
 {
  Storage::fake('local');Storage::fake('public');
  $seller=$this->seller();
  $this->actingAs($seller)->post('/seller/products',$this->payload()+['images'=>[UploadedFile::fake()->image('cover.png')]]);
  $product=Product::firstOrFail();$product->update(['status'=>ProductStatus::Published,'published_at'=>now()]);
  $this->actingAs($seller)->post("/seller/products/{$product->id}/images",['images'=>[UploadedFile::fake()->image('extra.webp')]])->assertRedirect();
  $this->assertSame(2,$product->images()->count());
  $first=$product->images()->orderBy('sort_order')->first();
  $this->actingAs($seller)->delete("/seller/products/{$product->id}/images/{$first->id}")->assertRedirect();
  Storage::disk('public')->assertMissing($first->path);
  $this->assertSame(1,$product->images()->count());
  $this->assertSame($product->images()->first()->path,$product->fresh()->cover_image_path);
 }
 public function test_other_sellers_cannot_manage_your_images(): void
 {
  Storage::fake('local');Storage::fake('public');
  $owner=$this->seller();
  $this->actingAs($owner)->post('/seller/products',$this->payload()+['images'=>[UploadedFile::fake()->image('mine.png')]]);
  $product=Product::firstOrFail();$image=$product->images()->firstOrFail();
  $intruder=$this->seller();
  $this->actingAs($intruder)->post("/seller/products/{$product->id}/images",['images'=>[UploadedFile::fake()->image('theirs.png')]])->assertForbidden();
  $this->actingAs($intruder)->delete("/seller/products/{$product->id}/images/{$image->id}")->assertForbidden();
 }
 public function test_product_page_renders_uploaded_cover_image(): void
 {
  Storage::fake('local');Storage::fake('public');
  $this->actingAs($this->seller())->post('/seller/products',$this->payload()+['images'=>[UploadedFile::fake()->image('cover.png'),UploadedFile::fake()->image('shot2.png')]]);
  $product=Product::firstOrFail();$product->update(['status'=>ProductStatus::Published,'published_at'=>now()]);
  $this->get("/products/{$product->slug}")->assertOk()->assertSee($product->cover_image_path);
 }
}
