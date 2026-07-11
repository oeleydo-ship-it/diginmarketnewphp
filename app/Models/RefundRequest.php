<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RefundRequest extends Model {protected $fillable=['number','user_id','order_id','order_item_id','product_id','seller_id','reason','description','requested_amount','approved_amount','status','seller_response','administrator_decision','decided_at'];protected function casts():array{return ['requested_amount'=>'decimal:2','approved_amount'=>'decimal:2','decided_at'=>'datetime'];}public function orderItem():BelongsTo{return $this->belongsTo(OrderItem::class);}}