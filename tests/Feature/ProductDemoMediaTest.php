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
class ProductDemoMediaTest extends TestCase
{
 use RefreshDatabase;
 private function seller(): User
 {
  $role=Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']);
  $seller=User::factory()->create();$seller->roles()->attach($role);
  SellerProfile::create(['user_id'=>$seller->id,'display_name'=>'Demo Studio','username'=>'demo-studio-'.$seller->id,'country'=>'AE','biography'=>'Approved seller','status'=>SellerStatus::Approved]);
  return $seller;
 }
 private function payload(): array
 {
  $category=Category::firstOrCreate(['slug'=>'demo-apps'],['name'=>'Demo Apps']);
  return ['category_id'=>$category->id,'title'=>'Demo App','short_description'=>'An app with a live demo.','description'=>str_repeat('Full-featured demo application details. ',3),'regular_price'=>'30.00','version_number'=>'1.0.0','release_title'=>'Initial release','archive'=>UploadedFile::fake()->create('demo.zip',10,'application/zip')];
 }
 public function test_demo_video_and_image_urls_are_saved_and_rendered(): void
 {
  Storage::fake('local');Storage::fake('public');
  $this->actingAs($this->seller())->post('/seller/products',$this->payload()+['demo_url'=>'https://demo.example.com/app','video_url'=>'https://www.youtube.com/watch?v=dQw4w9WgXcQ','image_urls'=>"https://cdn.example.com/shot-1.png\nhttps://cdn.example.com/shot-2.png"])->assertRedirect('/seller/products')->assertSessionHasNoErrors();
  $product=Product::firstOrFail();
  $this->assertSame('https://demo.example.com/app',$product->demo_url);
  $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',$product->videoEmbedUrl());
  $this->assertSame(2,$product->images()->where('disk','external')->count());
  $this->assertSame('https://cdn.example.com/shot-1.png',$product->cover_image_path);
  $product->update(['status'=>ProductStatus::Published,'published_at'=>now()]);
  $page=$this->get("/products/{$product->slug}");
  $page->assertOk()->assertSee('Live Preview')->assertSee('https://demo.example.com/app')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ',false)->assertSee('https://cdn.example.com/shot-1.png');
 }
 public function test_vimeo_urls_embed_and_unknown_hosts_do_not(): void
 {
  $product=new Product(['video_url'=>'https://vimeo.com/76979871']);
  $this->assertSame('https://player.vimeo.com/video/76979871',$product->videoEmbedUrl());
  $product->video_url='https://example.com/watch?v=abc123def';
  $this->assertNull($product->videoEmbedUrl());
 }
 public function test_insecure_urls_are_rejected(): void
 {
  Storage::fake('local');Storage::fake('public');
  $seller=$this->seller();
  $this->actingAs($seller)->post('/seller/products',$this->payload()+['demo_url'=>'http://insecure.example.com'])->assertSessionHasErrors('demo_url');
  $this->actingAs($seller)->post('/seller/products',$this->payload()+['image_urls'=>'http://insecure.example.com/a.png'])->assertSessionHasErrors('image_urls');
  $this->assertDatabaseCount('products',0);
 }
 public function test_image_urls_can_be_added_from_edit_page_and_count_toward_limit(): void
 {
  Storage::fake('local');Storage::fake('public');
  $seller=$this->seller();
  $this->actingAs($seller)->post('/seller/products',$this->payload());
  $product=Product::firstOrFail();
  $this->actingAs($seller)->post("/seller/products/{$product->id}/images",['image_urls'=>'https://cdn.example.com/late.png'])->assertRedirect()->assertSessionHasNoErrors();
  $this->assertSame('https://cdn.example.com/late.png',$product->fresh()->cover_image_path);
  $urls=collect(range(1,6))->map(fn($i)=>"https://cdn.example.com/extra-{$i}.png")->implode("\n");
  $this->actingAs($seller)->post("/seller/products/{$product->id}/images",['image_urls'=>$urls])->assertStatus(422);
  $this->assertSame(1,$product->images()->count());
 }
}
