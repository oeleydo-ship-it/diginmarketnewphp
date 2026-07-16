<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Models\Review;
use App\Models\SellerProfile;
use Illuminate\Contracts\View\View;
class SellerStorefrontController extends Controller
{
 public function show(string $username): View
 {
  $seller=SellerProfile::with('user')->where('username',$username)->where('status',SellerStatus::Approved)->firstOrFail();
  $user=$seller->user;
  $products=$user->products()->published()->with(['category','seller.sellerProfile'])->latest('published_at')->paginate(12)->withQueryString();
  $featured=$user->products()->published()->where('is_featured',true)->with(['category','seller.sellerProfile'])->latest('published_at')->limit(3)->get();
  // Ratings are aggregated from approved reviews across the seller's products.
  $ratingAgg=Review::where('status','approved')->whereHas('product',fn($q)=>$q->where('seller_id',$user->id))->selectRaw('AVG(rating) as avg, COUNT(*) as count')->first();
  $stats=[
   'products'=>$user->products()->published()->count(),
   'sales'=>(int)$user->products()->published()->sum('sales_count'),
   'followers'=>$user->followers()->count(),
   'following'=>$user->followedSellers()->count(),
   'rating'=>round((float)($ratingAgg->avg??0),1),
   'rating_count'=>(int)($ratingAgg->count??0),
  ];
  $bundles=\App\Models\Bundle::purchasable()->where('seller_id',$user->id)->with('products')->withCount('products')->latest()->limit(6)->get();
  return view('sellers.show',compact('seller','products','featured','stats','bundles'));
 }
}