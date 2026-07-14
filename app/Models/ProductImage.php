<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
class ProductImage extends Model
{
 protected $fillable=['product_id','disk','path','original_name','alt','sort_order'];
 public function product(): BelongsTo {return $this->belongsTo(Product::class);}
 public function url(): string {return $this->disk==='external'?$this->path:Storage::disk($this->disk)->url($this->path);}
}
