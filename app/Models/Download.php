<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Download extends Model {
 protected $fillable=['user_id','product_id','product_version_id','order_id','license_id','ip_address','user_agent','downloaded_at'];
 protected function casts():array{return ['downloaded_at'=>'datetime'];}
 public function product():BelongsTo{return $this->belongsTo(Product::class);}
 public function version():BelongsTo{return $this->belongsTo(ProductVersion::class,'product_version_id');}
 public function license():BelongsTo{return $this->belongsTo(License::class);}
 public function user():BelongsTo{return $this->belongsTo(User::class);}
}
