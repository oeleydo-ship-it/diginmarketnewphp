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
 protected $fillable=['seller_id','category_id','title','slug','short_description','description','cover_image_path','regular_price','extended_price','business_license_enabled','status','submitted_at','published_at','views_count','sales_count','average_rating','is_featured','is_trending','seo_title','seo_description'];
 protected function casts(): array { return ['status'=>ProductStatus::class,'regular_price'=>'decimal:2','extended_price'=>'decimal:2','business_license_enabled'=>'boolean','average_rating'=>'decimal:2','is_featured'=>'boolean','is_trending'=>'boolean','submitted_at'=>'datetime','published_at'=>'datetime']; }
 public function seller(): BelongsTo { return $this->belongsTo(User::class,'seller_id'); }
 public function category(): BelongsTo { return $this->belongsTo(Category::class); }
 public function versions(): HasMany { return $this->hasMany(ProductVersion::class); }
 public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id'); }
 /** Keep the denormalized cover in sync so listing pages never need the images relation. */
 public function refreshCoverImage(): void { $this->update(['cover_image_path'=>$this->images()->value('path')]); }
 public function reviewSubmissions(): HasMany { return $this->hasMany(ProductReviewSubmission::class); }
 public function wishlists(): BelongsToMany { return $this->belongsToMany(Wishlist::class,'wishlist_items')->withTimestamps(); }
 public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
 public function reviews(): HasMany { return $this->hasMany(Review::class); }
 public function comments(): HasMany { return $this->hasMany(Comment::class); }
 public function scopePublished(Builder $query): Builder { return $query->where('status',ProductStatus::Published)->whereNotNull('published_at'); }
}
