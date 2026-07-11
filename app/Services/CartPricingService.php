<?php
namespace App\Services;
use App\Models\Cart;
use App\Models\LicenseType;
use App\Models\Product;
class CartPricingService
{
 public function unitPrice(Product $product,LicenseType $license): string{return $license->slug==='extended'?(string)($product->extended_price?:$product->regular_price):(string)$product->regular_price;}
 public function reprice(Cart $cart): array
 {
  $subtotal=0;foreach($cart->items()->with(['product','licenseType'])->get() as $item){abort_unless($item->product->status->value==='published',422);$cents=(int)round(((float)$this->unitPrice($item->product,$item->licenseType))*100);$item->update(['unit_price'=>$cents/100,'tax'=>0,'discount'=>0,'total'=>$cents/100]);$subtotal+=$cents;}
  return ['subtotal'=>$subtotal/100,'discount'=>0.0,'tax'=>0.0,'fees'=>0.0,'total'=>$subtotal/100];
 }
}