<?php
namespace App\Services;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CouponService
{
 /** $subtotal is the eligible subtotal — for seller coupons, only that seller's items. */
 public function validate(Coupon $coupon,User $user,float $subtotal): void
 {
  if(!$coupon->is_active)throw ValidationException::withMessages(['coupon'=>'This coupon is no longer active.']);
  if($coupon->seller_id!==null&&$subtotal<=0)throw ValidationException::withMessages(['coupon'=>'This coupon only applies to products from '.(User::whereKey($coupon->seller_id)->value('name')??'its seller').', and your cart has none.']);
  if($coupon->starts_at&&$coupon->starts_at->isFuture())throw ValidationException::withMessages(['coupon'=>'This coupon is not active yet.']);
  if($coupon->ends_at&&$coupon->ends_at->isPast())throw ValidationException::withMessages(['coupon'=>'This coupon has expired.']);
  if($coupon->min_cart_total!==null&&$subtotal<(float)$coupon->min_cart_total)throw ValidationException::withMessages(['coupon'=>'Cart total is below the coupon minimum of $'.number_format((float)$coupon->min_cart_total,2).'.']);
  if($coupon->max_uses!==null&&$coupon->used_count>=$coupon->max_uses)throw ValidationException::withMessages(['coupon'=>'This coupon has reached its redemption limit.']);
  if($coupon->max_uses_per_user!==null&&CouponUsage::where('coupon_id',$coupon->id)->where('user_id',$user->id)->count()>=$coupon->max_uses_per_user)throw ValidationException::withMessages(['coupon'=>'You have already used this coupon.']);
 }
 /** Discount in cents for a subtotal in cents, never exceeding the subtotal. */
 public function discountCents(Coupon $coupon,int $subtotalCents): int
 {
  $discount=$coupon->type==='percent'?(int)round($subtotalCents*(float)$coupon->value/100):(int)round((float)$coupon->value*100);
  return max(0,min($discount,$subtotalCents));
 }
 /** Record redemption exactly once per order and bump the aggregate counter. */
 public function redeem(Order $order): void
 {
  if(!$order->coupon_id)return;
  DB::transaction(function()use($order){
   if(CouponUsage::where('order_id',$order->id)->exists())return;
   CouponUsage::create(['coupon_id'=>$order->coupon_id,'user_id'=>$order->user_id,'order_id'=>$order->id,'created_at'=>now()]);
   Coupon::whereKey($order->coupon_id)->increment('used_count');
  });
 }
}
