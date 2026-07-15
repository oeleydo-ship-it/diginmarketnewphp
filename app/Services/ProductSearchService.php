<?php
namespace App\Services;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
class ProductSearchService
{
 public function search(array $filters): LengthAwarePaginator
 {
  $query=Product::published()->with(['seller.sellerProfile','category']);
  $query->when($filters['q']??null,function(Builder $q,string $term){$term='%'.str_replace(['%','_'],['\\%','\\_'],$term).'%';$q->where(fn(Builder $x)=>$x->where('title','like',$term)->orWhere('short_description','like',$term)->orWhere('description','like',$term));});
  $query->when($filters['category']??null,fn(Builder $q,string $slug)=>$q->whereHas('category',fn(Builder $c)=>$c->where('slug',$slug)));
  $query->when(isset($filters['min_price']),fn(Builder $q)=>$q->where('regular_price','>=',$filters['min_price']));
  $query->when(isset($filters['max_price']),fn(Builder $q)=>$q->where('regular_price','<=',$filters['max_price']));
  $query->when(($filters['featured']??null)==='1',fn(Builder $q)=>$q->where('is_featured',true));
  $query->when(($filters['business']??null)==='1',fn(Builder $q)=>$q->where('business_license_enabled',true));
  $query->when(isset($filters['min_rating'])&&is_numeric($filters['min_rating']),fn(Builder $q)=>$q->where('average_rating','>=',(float)$filters['min_rating']));
  match($filters['sort']??'newest'){'price_low'=>$query->orderBy('regular_price'),'price_high'=>$query->orderByDesc('regular_price'),'popular'=>$query->orderByDesc('sales_count'),'rated'=>$query->orderByDesc('average_rating'),'title'=>$query->orderBy('title'),default=>$query->latest('published_at')};
  return $query->paginate(18)->withQueryString();
 }
}