<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class OrderItem extends Model {protected $fillable=['order_id','product_id','seller_id','product_version_id','license_type_id','product_title','seller_name','license_name','unit_price','discount','tax','platform_commission','seller_earning','total'];public function order():BelongsTo{return $this->belongsTo(Order::class);}public function product():BelongsTo{return $this->belongsTo(Product::class);}public function license():HasOne{return $this->hasOne(License::class);}public function refundRequest():HasOne{return $this->hasOne(RefundRequest::class);}}
