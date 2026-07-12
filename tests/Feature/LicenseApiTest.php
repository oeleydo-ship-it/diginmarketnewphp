<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LicenseApiTest extends TestCase
{
 use RefreshDatabase;
 private function license(int $activationLimit=2): array
 {
  $seller=User::factory()->create();$buyer=User::factory()->create();
  $category=Category::create(['name'=>'API Apps','slug'=>'api-apps']);
  $type=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'API Kit','slug'=>'api-kit','short_description'=>'API software','description'=>str_repeat('Complete API kit. ',5),'regular_price'=>100,'status'=>ProductStatus::Published,'published_at'=>now()]);
  $version=$product->versions()->create(['version_number'=>'1.0.0','release_title'=>'Stable','status'=>ProductVersionStatus::Published,'published_at'=>now()->subDay()]);
  $order=Order::create(['number'=>'DM-API-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>100,'total'=>100,'payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);
  $item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$type->id,'product_title'=>'API Kit','seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>100,'platform_commission'=>20,'seller_earning'=>80,'total'=>100]);
  $license=License::create(['license_key'=>'DM-API-LICENSE-KEY','product_id'=>$product->id,'product_version_id'=>$version->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'license_type_id'=>$type->id,'status'=>'active','activation_limit'=>$activationLimit,'support_expires_at'=>now()->addMonths(6)]);
  return compact('license','product','version');
 }
 public function test_verify_reports_valid_and_invalid_keys(): void
 {
  $d=$this->license();
  $this->postJson('/api/v1/licenses/verify',['license_key'=>'DM-API-LICENSE-KEY'])->assertOk()->assertJsonPath('valid',true)->assertJsonPath('product','API Kit');
  $this->postJson('/api/v1/licenses/verify',['license_key'=>'WRONG'])->assertStatus(422);
  $d['license']->update(['status'=>'suspended']);
  $this->postJson('/api/v1/licenses/verify',['license_key'=>'DM-API-LICENSE-KEY'])->assertStatus(422);
 }
 public function test_activation_limit_is_enforced_and_deactivation_frees_a_slot(): void
 {
  $this->license(2);
  $this->postJson('/api/v1/licenses/activate',['license_key'=>'DM-API-LICENSE-KEY','instance_id'=>'site-a.test'])->assertCreated();
  $this->postJson('/api/v1/licenses/activate',['license_key'=>'DM-API-LICENSE-KEY','instance_id'=>'site-a.test'])->assertCreated();
  $this->postJson('/api/v1/licenses/activate',['license_key'=>'DM-API-LICENSE-KEY','instance_id'=>'site-b.test'])->assertCreated()->assertJsonPath('activations_used',2);
  $this->postJson('/api/v1/licenses/activate',['license_key'=>'DM-API-LICENSE-KEY','instance_id'=>'site-c.test'])->assertStatus(422);
  $this->postJson('/api/v1/licenses/deactivate',['license_key'=>'DM-API-LICENSE-KEY','instance_id'=>'site-a.test'])->assertOk()->assertJsonPath('activations_used',1);
  $this->postJson('/api/v1/licenses/activate',['license_key'=>'DM-API-LICENSE-KEY','instance_id'=>'site-c.test'])->assertCreated();
  $this->assertDatabaseHas('license_activations',['instance_id'=>'site-a.test','status'=>'deactivated']);
 }
 public function test_update_check_reports_newer_published_version_and_support_state(): void
 {
  $d=$this->license();
  $this->postJson('/api/v1/licenses/update-check',['license_key'=>'DM-API-LICENSE-KEY'])->assertOk()->assertJsonPath('update_available',false)->assertJsonPath('support_active',true);
  $d['product']->versions()->create(['version_number'=>'2.0.0','release_title'=>'Major','status'=>ProductVersionStatus::Published,'published_at'=>now()]);
  $this->postJson('/api/v1/licenses/update-check',['license_key'=>'DM-API-LICENSE-KEY'])->assertOk()->assertJsonPath('update_available',true)->assertJsonPath('latest_version','2.0.0')->assertJsonPath('eligible',true);
  $d['license']->update(['support_expires_at'=>now()->subDay()]);
  $this->postJson('/api/v1/licenses/update-check',['license_key'=>'DM-API-LICENSE-KEY'])->assertOk()->assertJsonPath('support_active',false)->assertJsonPath('eligible',false);
 }
 public function test_license_api_is_rate_limited(): void
 {
  $this->license();
  for($i=0;$i<30;$i++)$this->postJson('/api/v1/licenses/verify',['license_key'=>'DM-API-LICENSE-KEY']);
  $this->postJson('/api/v1/licenses/verify',['license_key'=>'DM-API-LICENSE-KEY'])->assertStatus(429);
 }
}
