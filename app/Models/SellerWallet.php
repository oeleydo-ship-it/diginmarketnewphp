<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SellerWallet extends Model {protected $fillable=['seller_id','currency','pending_balance','available_balance','reserved_balance','withdrawn_balance','lifetime_earnings'];protected function casts():array{return ['pending_balance'=>'decimal:2','available_balance'=>'decimal:2','reserved_balance'=>'decimal:2','withdrawn_balance'=>'decimal:2','lifetime_earnings'=>'decimal:2'];}public function seller():BelongsTo{return $this->belongsTo(User::class,'seller_id');}public function transactions():HasMany{return $this->hasMany(WalletTransaction::class);}}