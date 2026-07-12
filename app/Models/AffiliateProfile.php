<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AffiliateProfile extends Model {protected $fillable=['user_id','code','commission_rate','clicks','referred_orders','total_earnings','status'];protected function casts():array{return ['commission_rate'=>'decimal:2','total_earnings'=>'decimal:2'];}public function user():BelongsTo{return $this->belongsTo(User::class);}public function earnings():HasMany{return $this->hasMany(AffiliateEarning::class);}}
