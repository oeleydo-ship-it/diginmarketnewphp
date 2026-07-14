<?php
namespace Tests\Feature;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InstallerAndBackupTest extends TestCase
{
 use RefreshDatabase;
 private string $lock;
 private string $backups;
 protected function setUp(): void
 {
  parent::setUp();
  $this->lock=sys_get_temp_dir().DIRECTORY_SEPARATOR.'dm-install-'.uniqid().'.lock';
  $this->backups=sys_get_temp_dir().DIRECTORY_SEPARATOR.'dm-backups-'.uniqid();
  config(['marketplace.install_lock'=>$this->lock,'marketplace.backup_path'=>$this->backups]);
 }
 protected function tearDown(): void
 {
  @unlink($this->lock);
  foreach(glob($this->backups.DIRECTORY_SEPARATOR.'*.zip')?:[] as $file)@unlink($file);
  @rmdir($this->backups);
  parent::tearDown();
 }
 public function test_uninstalled_marketplace_redirects_to_the_installer(): void
 {
  config(['marketplace.enforce_installer'=>true]);
  $this->get('/')->assertRedirect(route('install.show'));
  $this->get('/install')->assertOk()->assertSee('Install your marketplace')->assertSee('Server requirements');
 }
 public function test_installation_creates_admin_and_lock_then_disables_installer(): void
 {
  config(['marketplace.enforce_installer'=>true]);
  $this->post('/install',['site_name'=>'DiginMarket','admin_name'=>'Site Owner','admin_email'=>'owner@diginmarket.test','admin_password'=>'Sup3rSecret!!','admin_password_confirmation'=>'Sup3rSecret!!'])->assertRedirect(route('login'));
  $this->assertFileExists($this->lock);
  $owner=User::where('email','owner@diginmarket.test')->firstOrFail();
  $this->assertTrue($owner->hasRole('administrator'));
  $this->assertDatabaseHas('roles',['slug'=>'seller']);
  $this->assertDatabaseHas('license_types',['slug'=>'regular']);
  $this->assertDatabaseHas('settings',['key'=>'marketplace.name','value'=>'DiginMarket']);
  $this->get('/install')->assertRedirect(route('home'));
  $this->get('/login')->assertOk();
  $this->post('/install',['site_name'=>'X','admin_name'=>'X','admin_email'=>'x@x.test','admin_password'=>'Sup3rSecret!!','admin_password_confirmation'=>'Sup3rSecret!!'])->assertRedirect(route('home'));
 }
 public function test_backup_command_creates_archive_and_prunes_old_ones(): void
 {
  mkdir($this->backups,0755,true);
  foreach(range(1,9) as $i)file_put_contents($this->backups.DIRECTORY_SEPARATOR.sprintf('backup-2026010%d-000000.zip',$i),'old');
  $this->artisan('marketplace:backup')->assertSuccessful();
  $files=glob($this->backups.DIRECTORY_SEPARATOR.'backup-*.zip');
  $this->assertCount(7,$files);
  $newest=collect($files)->sortDesc()->first();
  $zip=new \ZipArchive();$zip->open($newest);
  $this->assertNotFalse($zip->locateName('manifest.json'));
  $zip->close();
 }
 public function test_admin_can_run_backup_from_system_panel(): void
 {
  $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $this->actingAs($admin)->post('/admin/system/backup')->assertRedirect();
  $this->assertNotEmpty(glob($this->backups.DIRECTORY_SEPARATOR.'backup-*.zip'));
  $this->assertDatabaseHas('audit_logs',['action'=>'system.backup_run']);
  $this->actingAs($admin)->get('/admin/system')->assertOk()->assertSee('Backups')->assertSee('backup-');
  $customer=User::factory()->create();
  $this->actingAs($customer)->post('/admin/system/backup')->assertForbidden();
 }
}
