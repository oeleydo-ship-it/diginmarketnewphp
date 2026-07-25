<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model {
 /**
  * Payment states that mean "the buyer's money reached us". A refund or dispute on ONE item moves
  * the whole order to partially_refunded/refunded/disputed, so entitlement and revenue checks must
  * accept these too — the per-item license status (refunded/revoked/suspended) is what actually
  * gates the affected item. Comparing against 'paid' alone silently revoked every OTHER item in
  * the order: downloads, license API, reviews, support and invoices all went dead.
  */
 public const SETTLED_STATUSES=['paid','partially_refunded','refunded','disputed'];
 protected $fillable=['number','user_id','affiliate_profile_id','coupon_id','coupon_code','currency','subtotal','discount','tax','fees','total','payment_status','status','payment_provider','provider_checkout_id','customer_ip','user_agent','paid_at'];
 protected function casts():array{return ['subtotal'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2','fees'=>'decimal:2','total'=>'decimal:2','paid_at'=>'datetime'];}
 public function user():BelongsTo{return $this->belongsTo(User::class);}
 public function items():HasMany{return $this->hasMany(OrderItem::class);}
 public function payments():HasMany{return $this->hasMany(Payment::class);}
 /** True once the order has been paid for, even if part of it was later refunded or disputed. */
 public function isSettled():bool{return in_array($this->payment_status,self::SETTLED_STATUSES,true);}
 public function scopeSettled(Builder $query):Builder{return $query->whereIn('payment_status',self::SETTLED_STATUSES);}
}