<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SupportTicket extends Model {protected $fillable=['number','user_id','seller_id','product_id','order_id','license_id','subject','priority','status','first_response_at','resolved_at'];protected function casts():array{return ['first_response_at'=>'datetime','resolved_at'=>'datetime'];}public function messages():HasMany{return $this->hasMany(SupportMessage::class);}public function customer():BelongsTo{return $this->belongsTo(User::class,'user_id');}public function seller():BelongsTo{return $this->belongsTo(User::class,'seller_id');}public function product():BelongsTo{return $this->belongsTo(Product::class);}public function order():BelongsTo{return $this->belongsTo(Order::class);}}
