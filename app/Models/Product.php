<?php
namespace App\Models;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Product extends Model
{
 use \Illuminate\Database\Eloquent\SoftDeletes;
 protected $fillable=['seller_id','category_id','title','slug','short_description','description','cover_image_path','demo_url','video_url','regular_price','extended_price','support_extension_price','support_extension_months','business_license_enabled','status','submitted_at','published_at','views_count','sales_count','average_rating','is_featured','is_trending','seo_title','seo_description'];
 protected function casts(): array { return ['status'=>ProductStatus::class,'regular_price'=>'decimal:2','extended_price'=>'decimal:2','business_license_enabled'=>'boolean','average_rating'=>'decimal:2','is_featured'=>'boolean','is_trending'=>'boolean','submitted_at'=>'datetime','published_at'=>'datetime']; }
 public function seller(): BelongsTo { return $this->belongsTo(User::class,'seller_id'); }
 public function category(): BelongsTo { return $this->belongsTo(Category::class); }
 public function versions(): HasMany { return $this->hasMany(ProductVersion::class); }
 public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id'); }
 /** Keep the denormalized cover in sync so listing pages never need the images relation. */
 public function refreshCoverImage(): void { $this->update(['cover_image_path'=>$this->images()->value('path')]); }
 /** Privacy-friendly embed URL for a YouTube/Vimeo video_url; null when unrecognized. */
 public function videoEmbedUrl(): ?string
 {
  if(!$this->video_url)return null;
  if(preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,20})~',$this->video_url,$m))return 'https://www.youtube-nocookie.com/embed/'.$m[1];
  if(preg_match('~vimeo\.com/(?:video/)?(\d+)~',$this->video_url,$m))return 'https://player.vimeo.com/video/'.$m[1];
  return null;
 }
 public function reviewSubmissions(): HasMany { return $this->hasMany(ProductReviewSubmission::class); }
 public function wishlists(): BelongsToMany { return $this->belongsToMany(Wishlist::class,'wishlist_items')->withTimestamps(); }
 public function bundles(): BelongsToMany { return $this->belongsToMany(Bundle::class); }
 public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
 public function reviews(): HasMany { return $this->hasMany(Review::class); }
 public function comments(): HasMany { return $this->hasMany(Comment::class); }
 public function scopePublished(Builder $query): Builder { return $query->where('status',ProductStatus::Published)->whereNotNull('published_at'); }
}
