<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Order extends Model {protected $fillable=['number','user_id','affiliate_profile_id','currency','subtotal','discount','tax','fees','total','payment_status','status','stripe_checkout_session_id','customer_ip','user_agent','paid_at'];protected function casts():array{return ['subtotal'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2','fees'=>'decimal:2','total'=>'decimal:2','paid_at'=>'datetime'];}public function user():BelongsTo{return $this->belongsTo(User::class);}public function items():HasMany{return $this->hasMany(OrderItem::class);}public function payments():HasMany{return $this->hasMany(Payment::class);}}