<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WalletTransaction extends Model {public $timestamps=false;protected $fillable=['uuid','seller_wallet_id','order_item_id','type','balance_bucket','amount','currency','reference','available_at','cleared_at','metadata','created_at'];protected function casts():array{return ['amount'=>'decimal:2','available_at'=>'datetime','cleared_at'=>'datetime','metadata'=>'array','created_at'=>'datetime'];}public function wallet():BelongsTo{return $this->belongsTo(SellerWallet::class,'seller_wallet_id');}}