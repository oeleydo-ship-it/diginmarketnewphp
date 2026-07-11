<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Review extends Model {protected $fillable=['product_id','user_id','order_item_id','rating','title','content','status','seller_response','helpful_votes'];public function product():BelongsTo{return $this->belongsTo(Product::class);}public function user():BelongsTo{return $this->belongsTo(User::class);}}