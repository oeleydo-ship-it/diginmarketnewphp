<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductFile extends Model
{
 protected $fillable=['product_version_id','disk','path','original_name','mime_type','extension','size','checksum','scan_status'];
 protected $hidden=['path'];
 public function version(): BelongsTo { return $this->belongsTo(ProductVersion::class,'product_version_id'); }
}