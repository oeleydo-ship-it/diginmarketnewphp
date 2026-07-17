<?php
namespace Tests\Feature;
use App\Jobs\ScanProductFile;
use App\Services\Scanners\BasicArchiveScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;
class FileScanningTest extends TestCase
{
 use RefreshDatabase;

 private function validZip(): string
 {
  $path = tempnam(sys_get_temp_dir(), 'dm-scan-').'.zip';
  $zip = new ZipArchive;
  $zip->open($path, ZipArchive::CREATE);
  $zip->addFromString('readme.txt', 'hello');
  $zip->close();
  return $path;
 }

 public function test_basic_scanner_accepts_valid_zip_and_flags_garbage(): void
 {
  $scanner = new BasicArchiveScanner;
  $valid = $this->validZip();
  $this->assertSame('clean', $scanner->scan($valid));
  $garbage = tempnam(sys_get_temp_dir(), 'dm-scan-');
  file_put_contents($garbage, 'not a zip at all');
  // Flagged for review, never 'infected' — a heuristic must not block downloads.
  $this->assertSame('error', $scanner->scan($garbage));
  @unlink($valid);
  @unlink($garbage);
 }

 public function test_scan_job_updates_status_and_alerts_admins_on_flags(): void
 {
  Storage::fake('local');
  $admin = \App\Models\User::factory()->create();
  $admin->roles()->attach(\App\Models\Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  $seller = \App\Models\User::factory()->create();
  $category = \App\Models\Category::create(['name' => 'Apps', 'slug' => 'apps']);
  $product = \App\Models\Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Scan Kit', 'slug' => 'scan-kit', 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 10, 'status' => \App\Enums\ProductStatus::Draft]);
  $version = $product->versions()->create(['version_number' => '1.0.0', 'release_title' => 'First', 'status' => \App\Enums\ProductVersionStatus::Draft]);
  Storage::disk('local')->put('products/bad.zip', 'garbage bytes');
  $file = $version->files()->create(['disk' => 'local', 'path' => 'products/bad.zip', 'original_name' => 'bad.zip', 'mime_type' => 'application/zip', 'extension' => 'zip', 'size' => 13, 'checksum' => hash('sha256', 'garbage bytes'), 'scan_status' => 'pending']);
  (new ScanProductFile($file->id))->handle(new BasicArchiveScanner);
  $this->assertSame('error', $file->fresh()->scan_status);
  $this->assertSame(1, $admin->unreadNotifications()->count());
 }

 public function test_uploading_a_product_dispatches_the_scan_job(): void
 {
  Queue::fake();
  Storage::fake('local');
  $seller = \App\Models\User::factory()->create();
  $seller->roles()->attach(\App\Models\Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']));
  \App\Models\SellerProfile::create(['user_id' => $seller->id, 'display_name' => 'S', 'username' => 's-'.$seller->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  \App\Models\Category::create(['name' => 'Apps', 'slug' => 'apps']);
  $this->actingAs($seller)->post('/seller/products', [
   'category_id' => 1, 'title' => 'Scanned Product', 'short_description' => 'Short.',
   'description' => str_repeat('Long enough description. ', 5), 'regular_price' => '10.00',
   'version_number' => '1.0.0', 'release_title' => 'First',
   'archive' => \Illuminate\Http\UploadedFile::fake()->create('product.zip', 100, 'application/zip'),
  ])->assertRedirect();
  Queue::assertPushed(ScanProductFile::class);
 }
}
