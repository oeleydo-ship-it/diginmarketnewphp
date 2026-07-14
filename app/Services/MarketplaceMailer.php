<?php
namespace App\Services;
use App\Mail\OrderReceiptMail;
use App\Mail\ProductReviewOutcomeMail;
use App\Mail\RefundDecisionMail;
use App\Mail\SellerSaleMail;
use App\Mail\WithdrawalDecisionMail;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Mail;
class MarketplaceMailer
{
 public function orderPaid(Order $order): void
 {
  $order->loadMissing(['user','items.license']);
  if($order->user)Mail::to($order->user)->queue(new OrderReceiptMail($order));
  foreach($order->items as $item){$seller=User::find($item->seller_id);if($seller&&$this->wants($seller,'email_sales'))Mail::to($seller)->queue(new SellerSaleMail($item));}
 }
 public function productReviewed(Product $product,string $outcome,string $notes=''): void
 {
  $seller=$product->seller()->first();
  if($seller)Mail::to($seller)->queue(new ProductReviewOutcomeMail($product,$outcome,$notes));
 }
 public function refundDecided(RefundRequest $refund): void
 {
  $buyer=User::find($refund->user_id);
  if($buyer)Mail::to($buyer)->queue(new RefundDecisionMail($refund));
 }
 public function withdrawalDecided(WithdrawalRequest $withdrawal): void
 {
  $seller=User::find($withdrawal->seller_id);
  if($seller)Mail::to($seller)->queue(new WithdrawalDecisionMail($withdrawal));
 }
 private function wants(User $user,string $channel): bool
 {
  $preference=NotificationPreference::where('user_id',$user->id)->first();
  return $preference===null||(bool)$preference->{$channel};
 }
}
