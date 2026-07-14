<?php
namespace Tests\Feature;
use App\Contracts\PayoutGateway;
use App\Enums\ProductStatus;
use App\Mail\OrderReceiptMail;
use App\Mail\ProductReviewOutcomeMail;
use App\Mail\RefundDecisionMail;
use App\Mail\SellerSaleMail;
use App\Mail\WithdrawalDecisionMail;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\Role;
use App\Models\SellerWallet;
use App\Models\StripeConnectedAccount;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\PaymentFulfillmentService;
use App\Services\ProductSubmissionService;
use App\Services\RefundService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class MailNotificationsTest extends TestCase
{
 use RefreshDatabase;
 private function sale():array
 {
  $seller=User::factory()->create();$buyer=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'mail-apps']);$licenseType=LicenseType::create(['name'=>'Regular','slug'=>'regular-mail','description'=>'Regular']);
  $product=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Mail App','slug'=>'mail-app','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'100.00','status'=>ProductStatus::Published,'published_at'=>now()]);
  $order=Order::create(['number'=>'DM-MAIL-1','user_id'=>$buyer->id,'currency'=>'USD','subtotal'=>100,'total'=>100,'payment_status'=>'pending','status'=>'awaiting_payment']);
  $item=$order->items()->create(['product_id'=>$product->id,'seller_id'=>$seller->id,'license_type_id'=>$licenseType->id,'product_title'=>$product->title,'seller_name'=>$seller->name,'license_name'=>'Regular','unit_price'=>100,'platform_commission'=>20,'seller_earning'=>80,'total'=>100]);
  return compact('seller','buyer','product','order','item');
 }
 public function test_order_fulfilment_queues_receipt_and_seller_sale_mail_once():void
 {
  Mail::fake();$sale=$this->sale();$service=app(PaymentFulfillmentService::class);
  $service->fulfill($sale['order']->id,'pi_mail_1');$service->fulfill($sale['order']->id,'pi_mail_1');
  Mail::assertQueued(OrderReceiptMail::class,fn($mail)=>$mail->hasTo($sale['buyer']->email));
  Mail::assertQueued(SellerSaleMail::class,fn($mail)=>$mail->hasTo($sale['seller']->email));
  Mail::assertQueuedCount(2);
 }
 public function test_seller_sale_mail_respects_notification_preferences():void
 {
  Mail::fake();$sale=$this->sale();
  NotificationPreference::create(['user_id'=>$sale['seller']->id,'email_sales'=>false]);
  app(PaymentFulfillmentService::class)->fulfill($sale['order']->id,'pi_mail_2');
  Mail::assertQueued(OrderReceiptMail::class);
  Mail::assertNotQueued(SellerSaleMail::class);
 }
 public function test_product_review_outcome_mails_are_sent_to_the_seller():void
 {
  Mail::fake();$admin=User::factory()->create();$admin->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'],['name'=>'Administrator']));
  $seller=User::factory()->create();$category=Category::create(['name'=>'Apps','slug'=>'review-mail-apps']);
  $approved=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Approve Mail','slug'=>'approve-mail','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'10.00','status'=>ProductStatus::Submitted,'submitted_at'=>now()]);
  $approved->reviewSubmissions()->create(['submitted_by'=>$seller->id,'status'=>'submitted','submitted_at'=>now()]);
  app(ProductSubmissionService::class)->approve($approved,$admin,'Looks good.');
  Mail::assertQueued(ProductReviewOutcomeMail::class,fn($mail)=>$mail->hasTo($seller->email)&&$mail->outcome==='approved');
  $changes=Product::create(['seller_id'=>$seller->id,'category_id'=>$category->id,'title'=>'Changes Mail','slug'=>'changes-mail','short_description'=>'App','description'=>str_repeat('Details ',10),'regular_price'=>'10.00','status'=>ProductStatus::Submitted,'submitted_at'=>now()]);
  $changes->reviewSubmissions()->create(['submitted_by'=>$seller->id,'status'=>'submitted','submitted_at'=>now()]);
  app(ProductSubmissionService::class)->requestChanges($changes,$admin,'Add screenshots.');
  Mail::assertQueued(ProductReviewOutcomeMail::class,fn($mail)=>$mail->hasTo($seller->email)&&$mail->outcome==='changes_requested');
 }
 public function test_refund_rejection_mails_the_buyer():void
 {
  Mail::fake();$admin=User::factory()->create();$sale=$this->sale();
  $sale['order']->update(['payment_status'=>'paid','status'=>'completed']);
  $refund=RefundRequest::create(['number'=>'RF-MAIL-1','user_id'=>$sale['buyer']->id,'order_id'=>$sale['order']->id,'order_item_id'=>$sale['item']->id,'product_id'=>$sale['product']->id,'seller_id'=>$sale['seller']->id,'reason'=>'not_as_described','description'=>'Missing feature.','requested_amount'=>100,'status'=>'submitted']);
  app(RefundService::class)->reject($refund,$admin,'Matches listing.');
  Mail::assertQueued(RefundDecisionMail::class,fn($mail)=>$mail->hasTo($sale['buyer']->email));
  app(RefundService::class)->reject($refund->fresh(),$admin,'Duplicate call.');
  Mail::assertQueuedCount(1);
 }
 public function test_withdrawal_decisions_mail_the_seller():void
 {
  $this->app->bind(PayoutGateway::class,fn()=>new class implements PayoutGateway{public function transfer(WithdrawalRequest $withdrawal,string $stripeAccountId,string $currency):array{return ['id'=>'tr_mail_'.$withdrawal->id];}});
  Mail::fake();$admin=User::factory()->create();
  $seller=User::factory()->create();$wallet=SellerWallet::create(['seller_id'=>$seller->id,'currency'=>'USD','available_balance'=>200]);
  StripeConnectedAccount::create(['seller_id'=>$seller->id,'stripe_account_id'=>'acct_mail_1','status'=>'enabled','details_submitted'=>true,'charges_enabled'=>true,'payouts_enabled'=>true]);
  $service=app(WithdrawalService::class);
  $paid=$service->request($wallet,60);$service->approve($paid,$admin,'Verified.');
  Mail::assertQueued(WithdrawalDecisionMail::class,fn($mail)=>$mail->hasTo($seller->email)&&$mail->withdrawal->status==='paid');
  $rejected=$service->request($wallet->fresh(),60);$service->reject($rejected,'Docs needed.');
  Mail::assertQueued(WithdrawalDecisionMail::class,fn($mail)=>$mail->hasTo($seller->email)&&$mail->withdrawal->status==='rejected');
  Mail::assertQueuedCount(2);
 }
}
