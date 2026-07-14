<?php
namespace App\Services;
use App\Models\Cart;
use App\Models\LicenseType;
use App\Models\Product;
class CartPricingService
{
 public function __construct(private CouponService $coupons){}
 public function unitPrice(Product $product,LicenseType $license): string{return $license->slug==='extended'?(string)($product->extended_price?:$product->regular_price):(string)$product->regular_price;}
 public function reprice(Cart $cart): array
 {
  $items=$cart->items()->with(['product','licenseType'])->get();
  $subtotal=0;$unitCents=[];
  foreach($items as $item){abort_unless($item->product->status->value==='published',422);$cents=(int)round(((float)$this->unitPrice($item->product,$item->licenseType))*100);$unitCents[$item->id]=$cents;$subtotal+=$cents;}
  $discount=0;
  if($cart->coupon_id&&($coupon=$cart->coupon()->first())){
   try{$this->coupons->validate($coupon,$cart->user()->firstOrFail(),$subtotal/100);$discount=$this->coupons->discountCents($coupon,$subtotal);}
   catch(\Illuminate\Validation\ValidationException){$cart->update(['coupon_id'=>null]);$discount=0;}
  }
  // Distribute the discount pro-rata across items (last item absorbs rounding)
  // so per-item totals — and therefore commissions — reflect the charged amounts.
  $allocated=0;
  foreach($items as $index=>$item){
   $cents=$unitCents[$item->id];
   $share=$subtotal>0?($index===count($items)-1?$discount-$allocated:(int)floor($discount*$cents/$subtotal)):0;
   $allocated+=$share;
   $item->update(['unit_price'=>$cents/100,'tax'=>0,'discount'=>$share/100,'total'=>($cents-$share)/100]);
  }
  return ['subtotal'=>$subtotal/100,'discount'=>$discount/100,'tax'=>0.0,'fees'=>0.0,'total'=>($subtotal-$discount)/100];
 }
}
