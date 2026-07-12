<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class License extends Model {protected $fillable=['license_key','product_id','product_version_id','user_id','order_item_id','license_type_id','status','activation_limit','activation_count','support_expires_at'];protected function casts():array{return ['support_expires_at'=>'datetime'];}public function orderItem():BelongsTo{return $this->belongsTo(OrderItem::class);}public function product():BelongsTo{return $this->belongsTo(Product::class);}public function version():BelongsTo{return $this->belongsTo(ProductVersion::class,'product_version_id');}public function activations():\Illuminate\Database\Eloquent\Relations\HasMany{return $this->hasMany(LicenseActivation::class);}}