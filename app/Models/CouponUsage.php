<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CouponUsage extends Model
{
 public $timestamps=false;
 protected $fillable=['coupon_id','user_id','order_id','created_at'];
 protected function casts(): array {return ['created_at'=>'datetime'];}
 public function coupon(): BelongsTo {return $this->belongsTo(Coupon::class);}
}
