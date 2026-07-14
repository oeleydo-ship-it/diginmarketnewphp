<?php
namespace App\Services;
use App\Models\Cart;
use App\Models\LicenseType;
use App\Models\Product;
class CartPricingService
{
 public function __construct(private CouponService $coupons){}
 public function unitPrice(Product $product,LicenseType $license): string{return $license->slug==='extended'?(string)($product->extended_price?:$product->regular_price):(string)$product->regular_price;}
 public function couponEligibleSubtotal(Cart $cart,\App\Models\Coupon $coupon): float
 {
  $sum=0;
  foreach($cart->items()->with(['product','licenseType'])->get() as $item)if($coupon->appliesTo($item->product))$sum+=(int)round(((float)$this->unitPrice($item->product,$item->licenseType))*100);
  return $sum/100;
 }
 public function reprice(Cart $cart): array
 {
  $items=$cart->items()->with(['product','licenseType'])->get();
  $subtotal=0;$unitCents=[];
  foreach($items as $item){abort_unless($item->product->status->value==='published',422);$cents=(int)round(((float)$this->unitPrice($item->product,$item->licenseType))*100);$unitCents[$item->id]=$cents;$subtotal+=$cents;}
  $discount=0;$eligibleIds=[];$eligibleSubtotal=0;
  if($cart->coupon_id&&($coupon=$cart->coupon()->first())){
   foreach($items as $item)if($coupon->appliesTo($item->product)){$eligibleIds[]=$item->id;$eligibleSubtotal+=$unitCents[$item->id];}
   try{$this->coupons->validate($coupon,$cart->user()->firstOrFail(),$eligibleSubtotal/100);$discount=$this->coupons->discountCents($coupon,$eligibleSubtotal);}
   catch(\Illuminate\Validation\ValidationException){$cart->update(['coupon_id'=>null]);$discount=0;$eligibleIds=[];}
  }
  // Distribute the discount pro-rata across eligible items (last eligible item absorbs
  // rounding) so per-item totals — and therefore commissions — reflect the charged amounts.
  $allocated=0;$lastEligible=end($eligibleIds);
  foreach($items as $item){
   $cents=$unitCents[$item->id];$share=0;
   if($eligibleSubtotal>0&&in_array($item->id,$eligibleIds,true))$share=$item->id===$lastEligible?$discount-$allocated:(int)floor($discount*$cents/$eligibleSubtotal);
   $allocated+=$share;
   $item->update(['unit_price'=>$cents/100,'tax'=>0,'discount'=>$share/100,'total'=>($cents-$share)/100]);
  }
  return ['subtotal'=>$subtotal/100,'discount'=>$discount/100,'tax'=>0.0,'fees'=>0.0,'total'=>($subtotal-$discount)/100];
 }
}
