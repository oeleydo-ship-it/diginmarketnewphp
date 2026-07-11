<?php
namespace Tests\Feature;
use App\Contracts\RefundGateway;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Models\User;
use App\Services\ReviewService;
use App\Services\SupportService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class EngagementWorkflowTest extends TestCase
{
 use RefreshDatabase;
 private function purchase():array
 {
  $seller=User::factory()->create();$buyer=User::factory()->create();$category=Category::create(['name'=>'Support Apps','slug'=>'support-apps']);$type=LicenseType::create(['name'=>'Regular','slug'=>'regular','description'=>'Regular']);$product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Helpdesk','slug'=>'helpdesk','short_description'=>'Helpdesk software','description'=>str_repeat('Complete helpdesk. ',5),'regular_price'=>100,'status'=>ProductStatus::Published,'published_at'=>now()]);$order=Order::create(['number'=>'DM-ENGAGE-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>100,'total'=>100,'payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);$item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$type->id,'product_title'=>'Helpdesk','seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>100,'platform_commission'=>20,'seller_earning'=>80,'total'=>100]);$payment=$order->payments()->create(['provider'=>'stripe','provider_payment_id'=>'pi_engage','amount'=>100,'currency'=>'USD','status'=>'succeeded','paid_at'=>now()]);$license=License::create(['license_key'=>'DM-ENGAGE-LICENSE','product_id'=>$product->id,'user_id'=>$buyer->id,'order_item_id'=>$item->id,'license_type_id'=>$type->id,'status'=>'active']);return compact('seller','buyer','product','order','item','license','payment');
 }
 public function test_only_verified_buyer_can_review_and_rating_is_recalculated():void
 {
  $p=$this->purchase();$outsider=User::factory()->create();$this->expectException(ValidationException::class);app(ReviewService::class)->create($outsider,$p['item']->load('order','license'),['rating'=>5,'title'=>'Great','content'=>'A detailed genuine review.']);
 }
 public function test_verified_review_and_purchase_bound_support_are_created():void
 {
  $p=$this->purchase();app(ReviewService::class)->create($p['buyer'],$p['item']->load('order','license'),['rating'=>4,'title'=>'Very useful','content'=>'A detailed genuine review.']);$this->assertEquals(4,$p['product']->fresh()->average_rating);$ticket=app(SupportService::class)->open($p['buyer'],$p['license']->load('orderItem.order'),['subject'=>'Installation help','message'=>'Please help me configure the application.']);$this->assertSame($p['seller']->id,$ticket->seller_id);$this->assertDatabaseHas('support_messages',['support_ticket_id'=>$ticket->id,'user_id'=>$p['buyer']->id]);
 }
 public function test_public_comments_support_threaded_replies():void
 {
  $p=$this->purchase();$this->actingAs($p['buyer'])->post('/products/'.$p['product']->id.'/comments',['content'=>'Does this support multiple teams?'])->assertRedirect();$parent=$p['product']->comments()->firstOrFail();$this->actingAs($p['seller'])->post('/products/'.$p['product']->id.'/comments',['content'=>'Yes, unlimited teams are supported.','parent_id'=>$parent->id])->assertRedirect();$this->assertDatabaseHas('comments',['parent_id'=>$parent->id,'user_id'=>$p['seller']->id]);
 }
 public function test_confirmed_refund_revokes_license_and_deducts_seller_ledger():void
 {
  $p=$this->purchase();$wallet=SellerWallet::create(['seller_id'=>$p['seller']->id,'currency'=>'USD','pending_balance'=>80,'lifetime_earnings'=>80]);$request=app(RefundService::class)->request($p['buyer'],$p['item']->load('order'),['reason'=>'Not as described','description'=>'The documented feature is not available in this version.']);$this->app->bind(RefundGateway::class,fn()=>new class implements RefundGateway{public function refund(\App\Models\Payment $payment,float $amount):array{return ['id'=>'re_test','status'=>'succeeded'];}});app(RefundService::class)->approve($request->load('orderItem.order.payments','orderItem.license'),User::factory()->create(),80,'Evidence accepted.');$this->assertSame('refunded',$p['license']->fresh()->status);$this->assertEquals(0,$wallet->fresh()->pending_balance);$this->assertDatabaseHas('wallet_transactions',['type'=>'refund_deduction','amount'=>-80]);
 }
 public function test_notification_preferences_are_user_controlled():void
 {
  $user=User::factory()->create();$this->actingAs($user)->put('/notification-preferences',['email_support'=>1,'in_app'=>1])->assertRedirect();$this->assertDatabaseHas('notification_preferences',['user_id'=>$user->id,'email_support'=>1,'email_marketing'=>0]);
 }
}