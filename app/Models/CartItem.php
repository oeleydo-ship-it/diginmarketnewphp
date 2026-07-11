<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CartItem extends Model {protected $fillable=['cart_id','product_id','license_type_id','unit_price','tax','discount','total'];protected function casts():array{return ['unit_price'=>'decimal:2','tax'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2'];}public function product():BelongsTo{return $this->belongsTo(Product::class);}public function licenseType():BelongsTo{return $this->belongsTo(LicenseType::class);}}