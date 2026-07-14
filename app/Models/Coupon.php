<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Coupon extends Model
{
 protected $fillable=['code','seller_id','description','type','value','min_cart_total','max_uses','max_uses_per_user','used_count','starts_at','ends_at','is_active'];
 protected function casts(): array {return ['value'=>'decimal:2','min_cart_total'=>'decimal:2','is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];}
 public function usages(): HasMany {return $this->hasMany(CouponUsage::class);}
 public function seller(): BelongsTo {return $this->belongsTo(User::class,'seller_id');}
 /** Seller coupons discount only that seller's items; platform coupons cover everything. */
 public function appliesTo(Product $product): bool {return $this->seller_id===null||$this->seller_id===$product->seller_id;}
}
