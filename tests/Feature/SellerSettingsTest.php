<?php
namespace Tests\Feature;
use App\Enums\SellerStatus;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SellerSettingsTest extends TestCase
{
 use RefreshDatabase;
 private function seller(): User
 {
  $seller=User::factory()->create();
  $seller->roles()->attach(Role::firstOrCreate(['slug'=>'seller'],['name'=>'Seller']));
  SellerProfile::create(['user_id'=>$seller->id,'full_name'=>'Orla Vance','display_name'=>'Orla Studio','username'=>'orla-studio','country'=>'AE','biography'=>'We build production-grade Laravel tooling.','business_name'=>'Orla LLC','status'=>SellerStatus::Approved,'reviewed_at'=>now()]);
  return $seller;
 }
 public function test_settings_page_shows_profile_and_submitted_data(): void
 {
  $this->actingAs($this->seller())->get('/seller/settings')->assertOk()
   ->assertSee('Storefront profile')->assertSee('Submitted application data')
   ->assertSee('Orla Studio')->assertSee('Orla LLC')->assertSee('Orla Vance')
   ->assertSee('@orla-studio')->assertSee('approved');
 }
 public function test_seller_can_update_storefront_settings_with_audit(): void
 {
  $seller=$this->seller();
  $this->actingAs($seller)->put('/seller/settings',['display_name'=>'Orla Digital','business_name'=>'Orla Digital FZ-LLC','website'=>'https://orla.dev','phone'=>'+971 50 000 0000','biography'=>'We build production-grade Laravel tooling and starter kits.','city'=>'Dubai'])->assertRedirect()->assertSessionHas('status');
  $profile=$seller->sellerProfile()->first();
  $this->assertSame('Orla Digital',$profile->display_name);
  $this->assertSame('Orla Digital FZ-LLC',$profile->business_name);
  $this->assertSame('https://orla.dev',$profile->website);
  $this->assertDatabaseHas('audit_logs',['action'=>'seller.settings_updated','entity_id'=>$profile->id]);
 }
 public function test_identity_fields_cannot_be_changed_through_settings(): void
 {
  $seller=$this->seller();
  $this->actingAs($seller)->put('/seller/settings',['display_name'=>'Orla Studio','biography'=>'We build production-grade Laravel tooling.','username'=>'hijacked','country'=>'US','full_name'=>'Someone Else','status'=>'pending']);
  $profile=$seller->sellerProfile()->first();
  $this->assertSame('orla-studio',$profile->username);
  $this->assertSame('AE',$profile->country);
  $this->assertSame('Orla Vance',$profile->full_name);
  $this->assertSame(SellerStatus::Approved,$profile->status);
 }
 public function test_settings_validation_rejects_bad_website(): void
 {
  $this->actingAs($this->seller())->put('/seller/settings',['display_name'=>'Orla Studio','biography'=>'We build production-grade Laravel tooling.','website'=>'javascript:alert(1)'])->assertSessionHasErrors('website');
 }
 public function test_non_sellers_cannot_access_seller_settings(): void
 {
  $this->actingAs(User::factory()->create())->get('/seller/settings')->assertForbidden();
 }
}
