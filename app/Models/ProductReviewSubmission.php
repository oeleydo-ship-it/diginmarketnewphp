<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductReviewSubmission extends Model
{
 protected $fillable=['product_id','submitted_by','reviewed_by','status','seller_notes','review_notes','submitted_at','reviewed_at'];
 protected function casts(): array { return ['submitted_at'=>'datetime','reviewed_at'=>'datetime']; }
 public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}