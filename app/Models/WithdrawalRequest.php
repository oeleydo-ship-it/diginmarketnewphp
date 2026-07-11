<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WithdrawalRequest extends Model {protected $fillable=['number','seller_id','seller_wallet_id','amount','fee','net_amount','status','administrator_note','stripe_transfer_id','stripe_payout_id','processed_at'];protected function casts():array{return ['amount'=>'decimal:2','fee'=>'decimal:2','net_amount'=>'decimal:2','processed_at'=>'datetime'];}public function wallet():BelongsTo{return $this->belongsTo(SellerWallet::class,'seller_wallet_id');}}