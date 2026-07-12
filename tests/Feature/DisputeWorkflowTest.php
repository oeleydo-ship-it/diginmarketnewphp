<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Models\User;
use App\Services\DisputeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class DisputeWorkflowTest extends TestCase
{
 use RefreshDatabase;
 private function purchase():array
 {
  $seller=User::factory()->create();$buyer=User::factory()->create();$category=Category::create(['name'=>'Dispute Apps','slug'=>'dispute-apps']);$type=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);$product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Ledger Kit','slug'=>'ledger-kit','short_description'=>'Ledger software','description'=>str_repeat('Complete ledger. ',5),'regular_price'=>100,'status'=>ProductStatus::Published,'published_at'=>now()]);$order=Order::create(['number'=>'DM-DISPUTE-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>100,'total'=>100,'payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);$item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$type->id,'product_title'=>'Ledger Kit','seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>100,'platform_commission'=>20,'seller_earning'=>80,'total'=>100]);$license=License::create(['license_key'=>'DM-DISPUTE-LICENSE','product_id'=>$product->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'license_type_id'=>$type->id,'status'=>'active']);return compact('seller','buyer','product','order','item','license');
 }
 public function test_only_the_paying_customer_can_open_a_dispute():void
 {
  $p=$this->purchase();$outsider=User::factory()->create();$this->expectException(ValidationException::class);app(DisputeService::class)->open($outsider,$p['item']->load('order','license'),['type'=>'quality','description'=>'This product is broken and does not run at all.']);
 }
 public function test_opening_a_dispute_suspends_the_license_and_blocks_duplicates():void
 {
  $p=$this->purchase();$dispute=app(DisputeService::class)->open($p['buyer'],$p['item']->load('order','license'),['type'=>'not_as_described','description'=>'The advertised module is completely missing from the package.']);
  $this->assertSame('open',$dispute->status);$this->assertSame('suspended',$p['license']->fresh()->status);
  $this->expectException(ValidationException::class);app(DisputeService::class)->open($p['buyer'],$p['item']->fresh()->load('order','license'),['type'=>'quality','description'=>'Trying to file the same dispute a second time here.']);
 }
 public function test_upholding_a_dispute_revokes_fulfilment_and_deducts_seller_ledger():void
 {
  $p=$this->purchase();$wallet=SellerWallet::create(['seller_id'=>$p['seller']->id,'currency'=>'USD','pending_balance'=>80,'lifetime_earnings'=>80]);$admin=User::factory()->create();
  $dispute=app(DisputeService::class)->open($p['buyer'],$p['item']->load('order','license'),['type'=>'quality','description'=>'The application corrupts data when saving records.']);
  app(DisputeService::class)->uphold($dispute->load('orderItem.order','orderItem.license'),$admin,100,'Chargeback confirmed by processor.');
  $this->assertSame('upheld',$dispute->fresh()->status);
  $this->assertSame('revoked',$p['license']->fresh()->status);
  $this->assertSame('disputed',$p['order']->fresh()->payment_status);
  $this->assertEquals(0,$wallet->fresh()->pending_balance);
  $this->assertDatabaseHas('wallet_transactions',['type'=>'dispute_deduction','amount'=>-80,'reference'=>'dispute:'.$dispute->id]);
  $this->assertDatabaseHas('audit_logs',['action'=>'dispute.upheld','entity_id'=>$dispute->id]);
 }
 public function test_dismissing_a_dispute_reinstates_the_license():void
 {
  $p=$this->purchase();$admin=User::factory()->create();
  $dispute=app(DisputeService::class)->open($p['buyer'],$p['item']->load('order','license'),['type'=>'unauthorized','description'=>'I did not authorize this purchase on my account.']);
  $this->assertSame('suspended',$p['license']->fresh()->status);
  app(DisputeService::class)->dismiss($dispute->load('orderItem.license'),$admin,'Purchase verified with the customer.');
  $this->assertSame('dismissed',$dispute->fresh()->status);
  $this->assertSame('active',$p['license']->fresh()->status);
  $this->assertDatabaseHas('audit_logs',['action'=>'dispute.dismissed','entity_id'=>$dispute->id]);
 }
 public function test_customer_can_file_dispute_over_http_and_admin_queue_lists_it():void
 {
  $p=$this->purchase();
  $this->actingAs($p['buyer'])->post('/disputes/'.$p['item']->id,['type'=>'quality','description'=>'The installer fails immediately after purchase every time.'])->assertRedirect();
  $this->assertDatabaseHas('disputes',['order_item_id'=>$p['item']->id,'status'=>'open']);
  $adminRole=\App\Models\Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']);$admin=User::factory()->create();$admin->roles()->attach($adminRole);
  $dispute=Dispute::firstOrFail();
  $this->actingAs($admin)->get('/admin/disputes')->assertOk()->assertSee($dispute->number);
 }
}
