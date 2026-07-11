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
 protected $fillable=['seller_id','category_id','title','slug','short_description','description','regular_price','extended_price','status','submitted_at','published_at','views_count','sales_count','average_rating','is_featured','is_trending','seo_title','seo_description'];
 protected function casts(): array { return ['status'=>ProductStatus::class,'regular_price'=>'decimal:2','extended_price'=>'decimal:2','average_rating'=>'decimal:2','is_featured'=>'boolean','is_trending'=>'boolean','submitted_at'=>'datetime','published_at'=>'datetime']; }
 public function seller(): BelongsTo { return $this->belongsTo(User::class,'seller_id'); }
 public function category(): BelongsTo { return $this->belongsTo(Category::class); }
 public function versions(): HasMany { return $this->hasMany(ProductVersion::class); }
 public function reviewSubmissions(): HasMany { return $this->hasMany(ProductReviewSubmission::class); }
 public function wishlists(): BelongsToMany { return $this->belongsToMany(Wishlist::class,'wishlist_items')->withTimestamps(); }
 public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
 public function reviews(): HasMany { return $this->hasMany(Review::class); }
 public function comments(): HasMany { return $this->hasMany(Comment::class); }
 public function scopePublished(Builder $query): Builder { return $query->where('status',ProductStatus::Published)->whereNotNull('published_at'); }
}
