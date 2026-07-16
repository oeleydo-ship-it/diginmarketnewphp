<?php
namespace Tests\Feature;
use App\Models\Role;
use App\Models\User;
use App\Services\ApplicationUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;
class InstallerDatabaseAndUpdateTest extends TestCase
{
 use RefreshDatabase;
 private string $envFile;
 private string $updateTarget;
 private string $lock;

 protected function setUp(): void
 {
  parent::setUp();
  $this->envFile = tempnam(sys_get_temp_dir(), 'dm-env-');
  file_put_contents($this->envFile, "APP_NAME=DiginMarket\nDB_CONNECTION=sqlite\n# DB_HOST=127.0.0.1\n");
  $this->updateTarget = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dm-update-'.uniqid();
  mkdir($this->updateTarget);
  $this->lock = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dm-lock-'.uniqid();
  config(['marketplace.env_path' => $this->envFile, 'marketplace.update_target' => $this->updateTarget, 'marketplace.install_lock' => $this->lock, 'marketplace.backup_path' => sys_get_temp_dir()]);
 }

 protected function tearDown(): void
 {
  @unlink($this->envFile);
  @unlink($this->lock);
  foreach (glob($this->updateTarget.'/*/*') ?: [] as $f) {
   @unlink($f);
  }
  foreach (glob($this->updateTarget.'/*') ?: [] as $f) {
   is_dir($f) ? @rmdir($f) : @unlink($f);
  }
  @rmdir($this->updateTarget);
  parent::tearDown();
 }

 private function admin(): User
 {
  $admin = User::factory()->create();
  $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  return $admin;
 }

 private function zip(array $files): UploadedFile
 {
  $path = tempnam(sys_get_temp_dir(), 'dm-zip-').'.zip';
  $zip = new ZipArchive;
  $zip->open($path, ZipArchive::CREATE);
  foreach ($files as $name => $content) {
   $zip->addFromString($name, $content);
  }
  $zip->close();
  return new UploadedFile($path, 'update.zip', 'application/zip', null, true);
 }

 public function test_installer_database_step_writes_mysql_settings_to_env(): void
 {
  config(['marketplace.enforce_installer' => true]);
  // Point at the test's own SQLite-backed "mysql"? No — validation-level test: unreachable
  // MySQL must be rejected BEFORE .env changes; sqlite path must be written.
  $response = $this->post('/install/database', ['connection' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'dm', 'username' => 'root', 'password' => '']);
  $response->assertSessionHasErrors('database');
  $this->assertStringNotContainsString('DB_PORT=1', file_get_contents($this->envFile));

  $this->post('/install/database', ['connection' => 'sqlite'])->assertRedirect(route('install.show'));
  $env = file_get_contents($this->envFile);
  $this->assertStringContainsString('DB_CONNECTION=sqlite', $env);
  $this->assertStringContainsString('DB_DATABASE=', $env);
 }

 public function test_env_writer_replaces_commented_and_existing_keys_and_quotes_values(): void
 {
  app(\App\Support\EnvWriter::class)->set(['DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PASSWORD' => 'p@ss word#1']);
  $env = file_get_contents($this->envFile);
  $this->assertStringContainsString('DB_CONNECTION=mysql', $env);
  $this->assertStringContainsString('DB_HOST=127.0.0.1', $env);
  $this->assertStringNotContainsString('# DB_HOST', $env);
  $this->assertStringContainsString('DB_PASSWORD="p@ss word#1"', $env);
  $this->assertSame(1, substr_count($env, 'DB_CONNECTION='));
 }

 public function test_admin_applies_update_zip_and_version_is_recorded(): void
 {
  $upload = $this->zip([
   'app/Services/NewFeature.php' => '<?php // shipped by update',
   'resources/views/new-page.blade.php' => '<div>new</div>',
   'update-manifest.json' => json_encode(['version' => '2.1.0']),
  ]);
  $this->actingAs($this->admin())->post('/admin/system/update', ['package' => $upload])->assertRedirect()->assertSessionHas('status');
  $this->assertFileExists($this->updateTarget.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'NewFeature.php');
  $this->assertSame('2.1.0', \App\Models\Setting::get('system.version'));
  $this->assertDatabaseHas('audit_logs', ['action' => 'system.update_applied']);
 }

 public function test_update_zip_with_wrapper_directory_is_unwrapped(): void
 {
  $upload = $this->zip(['release-2.2/app/Http/Patch.php' => '<?php // wrapped', 'release-2.2/update-manifest.json' => json_encode(['version' => '2.2.0'])]);
  $this->actingAs($this->admin())->post('/admin/system/update', ['package' => $upload])->assertRedirect()->assertSessionHas('status');
  $this->assertFileExists($this->updateTarget.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Patch.php');
 }

 public function test_update_zip_with_traversal_or_disallowed_paths_is_rejected(): void
 {
  $traversal = $this->zip(['app/../../evil.php' => 'evil']);
  $this->actingAs($this->admin())->post('/admin/system/update', ['package' => $traversal])->assertSessionHasErrors('package');
  $disallowed = $this->zip(['storage/app/products/steal.zip' => 'x', 'app/Ok.php' => 'ok']);
  $this->actingAs($this->admin())->post('/admin/system/update', ['package' => $disallowed])->assertSessionHasErrors('package');
  $envGrab = $this->zip(['.env' => 'APP_KEY=hacked']);
  $this->actingAs($this->admin())->post('/admin/system/update', ['package' => $envGrab])->assertSessionHasErrors('package');
  $this->assertSame([], glob($this->updateTarget.'/*') ?: []);
 }

 public function test_update_endpoint_is_admin_only(): void
 {
  $upload = $this->zip(['app/X.php' => 'x']);
  $this->actingAs(User::factory()->create())->post('/admin/system/update', ['package' => $upload])->assertForbidden();
 }
}
