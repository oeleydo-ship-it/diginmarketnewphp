<?php
namespace App\Services;
use App\Models\CommissionRule;
use App\Models\Product;
class CommissionResolver
{
 public function resolve(Product $product,float $gross):array
 {
  $scopes=[['product',$product->id],['seller',$product->seller_id],['category',$product->category_id],['global',null]];$rule=null;foreach($scopes as [$type,$id]){$query=CommissionRule::where('scope_type',$type)->where('is_active',true)->where(fn($q)=>$q->whereNull('starts_at')->orWhere('starts_at','<=',now()))->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',now()));$id===null?$query->whereNull('scope_id'):$query->where('scope_id',$id);$rule=$query->orderByDesc('priority')->first();if($rule)break;}$rate=(float)($rule?->rate??config('marketplace.default_commission_rate',20));$fixed=(float)($rule?->fixed_fee??0);$commission=min($gross,round(($gross*$rate/100)+$fixed,2));return ['rate'=>$rate,'fixed_fee'=>$fixed,'commission'=>$commission,'seller_earning'=>round($gross-$commission,2),'rule_id'=>$rule?->id];
 }
}