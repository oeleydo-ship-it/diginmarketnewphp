<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WithdrawalRequest extends Model {protected $fillable=['number','seller_id','seller_wallet_id','amount','fee','net_amount','status','payout_method','payout_details','administrator_note','stripe_transfer_id','stripe_payout_id','payout_reference','processed_at'];protected function casts():array{return ['amount'=>'decimal:2','fee'=>'decimal:2','net_amount'=>'decimal:2','payout_details'=>'array','processed_at'=>'datetime'];}public function wallet():BelongsTo{return $this->belongsTo(SellerWallet::class,'seller_wallet_id');}
 public function methodLabel():string{return ['stripe'=>'Stripe Connect','paypal'=>'PayPal','bank'=>'Bank transfer'][$this->payout_method]??'Stripe Connect';}
 /** Human-readable payout destination for the admin queue. */
 public function payoutSummary():string{$d=$this->payout_details??[];return match($this->payout_method){'paypal'=>$d['email']??'—','bank'=>trim(($d['bank_name']??'').' ····'.substr($d['account_number']??'',-4)),default=>'Stripe connected account'};}}