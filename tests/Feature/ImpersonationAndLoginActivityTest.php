<?php
namespace Tests\Feature;
use App\Http\Controllers\ImpersonationController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImpersonationAndLoginActivityTest extends TestCase
{
 use RefreshDatabase;
 private function admin(): User { $admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));return $admin; }
 public function test_login_records_activity_and_last_login_timestamp(): void
 {
  $user=User::factory()->create(['password'=>bcrypt('Password!234')]);
  $this->post('/login',['email'=>$user->email,'password'=>'Password!234'])->assertRedirect();
  $this->assertDatabaseHas('login_activities',['user_id'=>$user->id]);
  $this->assertNotNull($user->fresh()->last_login_at);
 }
 public function test_admin_can_impersonate_a_customer_with_audit_trail(): void
 {
  $admin=$this->admin();$customer=User::factory()->create();
  $this->actingAs($admin)->post("/admin/users/{$customer->id}/impersonate")->assertRedirect(route('dashboard'));
  $this->assertAuthenticatedAs($customer);
  $this->assertSame($admin->id,session(ImpersonationController::SESSION_KEY));
  $this->assertDatabaseHas('audit_logs',['action'=>'user.impersonation_started','user_id'=>$admin->id,'entity_id'=>$customer->id]);
  $this->get('/dashboard')->assertOk()->assertSee('Stop impersonating');
  $this->post('/impersonation/stop')->assertRedirect(route('admin.users.index'));
  $this->assertAuthenticatedAs($admin);
  $this->assertNull(session(ImpersonationController::SESSION_KEY));
  $this->assertDatabaseHas('audit_logs',['action'=>'user.impersonation_stopped','user_id'=>$admin->id]);
 }
 public function test_administrators_cannot_be_impersonated(): void
 {
  $admin=$this->admin();$other=$this->admin();
  $this->actingAs($admin)->post("/admin/users/{$other->id}/impersonate")->assertForbidden();
  $this->assertAuthenticatedAs($admin);
 }
 public function test_customers_cannot_start_impersonation(): void
 {
  $customer=User::factory()->create();$target=User::factory()->create();
  $this->actingAs($customer)->post("/admin/users/{$target->id}/impersonate")->assertForbidden();
 }
 public function test_impersonation_expires_automatically(): void
 {
  $admin=$this->admin();$customer=User::factory()->create();
  $this->actingAs($admin)->post("/admin/users/{$customer->id}/impersonate");
  session()->put(ImpersonationController::EXPIRES_KEY,now()->subMinute()->timestamp);
  $this->get('/dashboard')->assertRedirect(route('admin.users.index'));
  $this->assertAuthenticatedAs($admin);
  $this->assertDatabaseHas('audit_logs',['action'=>'user.impersonation_expired']);
 }
}
