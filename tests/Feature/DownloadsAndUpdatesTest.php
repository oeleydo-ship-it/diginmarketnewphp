<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Jobs\NotifyBuyersOfProductUpdate;
use App\Mail\ProductUpdateMail;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Buyers own the product, not the build they happened to download on the day they paid: a licence
 * must reach every version the seller publishes afterwards.
 */
class DownloadsAndUpdatesTest extends TestCase
{
 use RefreshDatabase;

 /** Paid buyer holding a licence pinned to v1.0.0, with a real file behind each version. */
 private function purchase(): array
 {
  Storage::fake('local');
  $seller=User::factory()->create();
  $buyer=User::factory()->create();
  $category=Category::create(['name'=>'Update Apps','slug'=>'update-apps']);
  $type=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Sync Engine','slug'=>'sync-engine','short_description'=>'Sync things','description'=>str_repeat('Sync engine. ',5),'regular_price'=>60,'status'=>ProductStatus::Published,'published_at'=>now()]);
  $v1=$this->version($product,'1.0.0',ProductVersionStatus::Published,now()->subMonth());
  $order=Order::create(['number'=>'DM-UPD-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>60,'total'=>60,'payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);
  $item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'product_version_id'=>$v1->id,'license_type_id'=>$type->id,'product_title'=>'Sync Engine','seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>60,'platform_commission'=>12,'seller_earning'=>48,'total'=>60]);
  $license=License::create(['license_key'=>'DM-UPDATE-KEY','product_id'=>$product->id,'product_version_id'=>$v1->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'license_type_id'=>$type->id,'status'=>'active','support_expires_at'=>now()->addMonths(6)]);
  return compact('seller','buyer','product','order','item','license','v1','type');
 }

 private function version(Product $product,string $number,ProductVersionStatus $status,?\Illuminate\Support\Carbon $publishedAt=null): ProductVersion
 {
  $version=$product->versions()->create(['version_number'=>$number,'release_title'=>'Release '.$number,'release_notes'=>'Notes for '.$number,'status'=>$status,'published_at'=>$publishedAt]);
  Storage::disk('local')->put('products/'.$product->id.'/'.$number.'.zip','archive '.$number);
  $version->files()->create(['disk'=>'local','path'=>'products/'.$product->id.'/'.$number.'.zip','original_name'=>$number.'.zip','mime_type'=>'application/zip','extension'=>'zip','size'=>11,'checksum'=>hash('sha256',$number),'scan_status'=>'clean']);
  return $version;
 }

 public function test_unversioned_download_serves_the_newest_published_version(): void
 {
  $p=$this->purchase();
  $v2=$this->version($p['product'],'2.0.0',ProductVersionStatus::Published,now());
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$p['license']->id]);
  $this->actingAs($p['buyer'])->get($url)->assertOk();
  // The newest release is what the buyer receives, and the log records which build they got.
  $this->assertDatabaseHas('downloads',['license_id'=>$p['license']->id,'product_version_id'=>$v2->id]);
 }

 public function test_buyer_can_still_fetch_the_exact_version_they_purchased(): void
 {
  $p=$this->purchase();
  $this->version($p['product'],'2.0.0',ProductVersionStatus::Published,now());
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$p['license']->id,'version'=>$p['v1']->id]);
  $this->actingAs($p['buyer'])->get($url)->assertOk();
  $this->assertDatabaseHas('downloads',['license_id'=>$p['license']->id,'product_version_id'=>$p['v1']->id]);
 }

 public function test_unpublished_versions_and_other_products_are_not_downloadable(): void
 {
  $p=$this->purchase();
  $pending=$this->version($p['product'],'3.0.0',ProductVersionStatus::PendingReview);
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$p['license']->id,'version'=>$pending->id]);
  $this->actingAs($p['buyer'])->get($url)->assertNotFound();
  $other=Product::create(['seller_id'=>$p['seller']->id,'category_id'=>$p['product']->category_id,'title'=>'Other Kit','slug'=>'other-kit','short_description'=>'Other','description'=>str_repeat('Other kit. ',5),'regular_price'=>10,'status'=>ProductStatus::Published,'published_at'=>now()]);
  $foreign=$this->version($other,'1.0.0',ProductVersionStatus::Published,now());
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$p['license']->id,'version'=>$foreign->id]);
  $this->actingAs($p['buyer'])->get($url)->assertNotFound();
 }

 public function test_a_version_without_a_file_returns_404_instead_of_a_server_error(): void
 {
  $p=$this->purchase();
  $empty=$p['product']->versions()->create(['version_number'=>'4.0.0','release_title'=>'Empty','status'=>ProductVersionStatus::Published,'published_at'=>now()]);
  $url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$p['license']->id,'version'=>$empty->id]);
  $this->actingAs($p['buyer'])->get($url)->assertNotFound();
 }

 public function test_downloads_library_lists_versions_and_history(): void
 {
  $p=$this->purchase();
  $this->version($p['product'],'2.0.0',ProductVersionStatus::Published,now());
  $this->actingAs($p['buyer'])->get('/downloads')->assertOk()->assertSee('Sync Engine')->assertSee('v2.0.0')->assertSee('Update available');
  $this->get(URL::temporarySignedRoute('downloads.show',now()->addMinutes(5),['license'=>$p['license']->id]))->assertOk();
  $this->get('/downloads')->assertOk()->assertSee('Download history');
  $this->assertDatabaseCount('downloads',1);
 }

 public function test_purchase_page_offers_every_entitled_version(): void
 {
  $p=$this->purchase();
  $this->version($p['product'],'2.0.0',ProductVersionStatus::Published,now());
  $this->actingAs($p['buyer'])->get('/purchases/'.$p['order']->id)->assertOk()->assertSee('Update available')->assertSee('All versions (2)');
 }

 public function test_approving_a_version_queues_an_update_notice_for_existing_buyers(): void
 {
  $p=$this->purchase();
  Mail::fake();
  $pending=$this->version($p['product'],'2.0.0',ProductVersionStatus::PendingReview);
  $admin=User::factory()->create();
  app(ProductSubmissionService::class)->approveVersion($pending,$admin,'Looks good.');
  (new NotifyBuyersOfProductUpdate($pending->id))->handle();
  Mail::assertQueued(ProductUpdateMail::class,fn($mail)=>$mail->hasTo($p['buyer']->email));
 }

 public function test_buyers_who_opted_out_of_product_updates_are_not_mailed(): void
 {
  $p=$this->purchase();
  NotificationPreference::create(['user_id'=>$p['buyer']->id,'email_product_updates'=>false]);
  Mail::fake();
  $v2=$this->version($p['product'],'2.0.0',ProductVersionStatus::Published,now());
  (new NotifyBuyersOfProductUpdate($v2->id))->handle();
  Mail::assertNothingQueued();
 }

 public function test_resubmitting_a_listing_keeps_published_versions_published(): void
 {
  $p=$this->purchase();
  $rejected=$this->version($p['product'],'9.9.9',ProductVersionStatus::Rejected);
  $draft=$this->version($p['product'],'2.0.0',ProductVersionStatus::Draft);
  app(ProductSubmissionService::class)->submit($p['product'],$p['seller']);
  // Only the unreviewed draft enters the queue; the live release keeps serving buyers and the
  // previously rejected upload stays rejected.
  $this->assertSame(ProductVersionStatus::Published,$p['v1']->fresh()->status);
  $this->assertSame(ProductVersionStatus::PendingReview,$draft->fresh()->status);
  $this->assertSame(ProductVersionStatus::Rejected,$rejected->fresh()->status);
  $adminRole=Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']);
  $admin=User::factory()->create();$admin->roles()->attach($adminRole);
  app(ProductSubmissionService::class)->approve($p['product']->fresh(),$admin);
  $this->assertSame(ProductVersionStatus::Published,$draft->fresh()->status);
  $this->assertSame(ProductVersionStatus::Rejected,$rejected->fresh()->status);
 }
}
