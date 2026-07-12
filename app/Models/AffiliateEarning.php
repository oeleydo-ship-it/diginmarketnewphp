<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AffiliateEarning extends Model {protected $fillable=['affiliate_profile_id','order_id','amount','currency','status'];protected function casts():array{return ['amount'=>'decimal:2'];}public function profile():BelongsTo{return $this->belongsTo(AffiliateProfile::class,'affiliate_profile_id');}public function order():BelongsTo{return $this->belongsTo(Order::class);}}
