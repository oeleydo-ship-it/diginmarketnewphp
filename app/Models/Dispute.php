<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Dispute extends Model {protected $fillable=['number','user_id','order_id','order_item_id','product_id','seller_id','type','description','disputed_amount','resolved_amount','status','seller_response','administrator_decision','resolved_at'];protected function casts():array{return ['disputed_amount'=>'decimal:2','resolved_amount'=>'decimal:2','resolved_at'=>'datetime'];}public function orderItem():BelongsTo{return $this->belongsTo(OrderItem::class);}public function user():BelongsTo{return $this->belongsTo(User::class);}public function seller():BelongsTo{return $this->belongsTo(User::class,'seller_id');}}
