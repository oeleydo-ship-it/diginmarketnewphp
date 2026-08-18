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
 /** First non-infected archive on this version (private disk). */
 public function downloadableFile(): ?ProductFile
 {
  return $this->relationLoaded('files')
   ? $this->files->first(fn (ProductFile $file) => $file->scan_status !== 'infected')
   : $this->files()->where('scan_status', '!=', 'infected')->orderBy('id')->first();
 }
}