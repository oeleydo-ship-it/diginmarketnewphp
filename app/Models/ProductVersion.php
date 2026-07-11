<?php
namespace App\Models;
use App\Enums\ProductVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProductVersion extends Model
{
 protected $fillable=['product_id','version_number','release_title','release_notes','status','published_at'];
 protected function casts(): array { return ['status'=>ProductVersionStatus::class,'published_at'=>'datetime']; }
 public function product(): BelongsTo { return $this->belongsTo(Product::class); }
 public function files(): HasMany { return $this->hasMany(ProductFile::class); }
}