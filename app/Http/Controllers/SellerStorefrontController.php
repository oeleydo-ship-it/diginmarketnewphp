<?php

namespace App\Http\Controllers;

use App\Enums\SellerStatus;
use App\Models\Bundle;
use App\Models\Review;
use App\Models\SellerProfile;
use Illuminate\Contracts\View\View;

class SellerStorefrontController extends Controller
{
    public function show(string $username): View
    {
        $seller = SellerProfile::with('user')->where('username', $username)->where('status', SellerStatus::Approved)->firstOrFail();
        $user = $seller->user;
        $products = $user->products()->published()->with(['category', 'seller.sellerProfile'])->latest('published_at')->paginate(12)->withQueryString();
        $featured = $user->products()->published()->where('is_featured', true)->with(['category', 'seller.sellerProfile'])->latest('published_at')->limit(3)->get();
        // Ratings are aggregated from approved reviews across the seller's products.
        $ratingAgg = Review::where('status', 'approved')->whereHas('product', fn ($q) => $q->where('seller_id', $user->id))->selectRaw('AVG(rating) as avg, COUNT(*) as count')->first();
        $stats = [
            'products' => $user->products()->published()->count(),
            'sales' => (int) $user->products()->published()->sum('sales_count'),
            'followers' => $user->followers()->count(),
            'following' => $user->followedSellers()->count(),
            'rating' => round((float) ($ratingAgg->avg ?? 0), 1),
            'rating_count' => (int) ($ratingAgg->count ?? 0),
        ];
        $bundles = Bundle::purchasable()->where('seller_id', $user->id)->with('products')->withCount('products')->latest()->limit(6)->get();
        // Achievement badges are computed from live stats — no schema, no drift, no admin upkeep.
        $badges = array_values(array_filter([
            $stats['products'] >= 10 ? ['icon' => 'workspace_premium', 'label' => 'Power Author'] : null,
            $stats['sales'] >= 50 ? ['icon' => 'local_fire_department', 'label' => 'Top Seller'] : null,
            ($stats['rating'] >= 4.5 && $stats['rating_count'] >= 10) ? ['icon' => 'hotel_class', 'label' => 'Highly Rated'] : null,
            $user->created_at->lte(now()->subYear()) ? ['icon' => 'military_tech', 'label' => 'Veteran'] : null,
            $stats['followers'] >= 25 ? ['icon' => 'diversity_3', 'label' => 'Community Favourite'] : null,
        ]));

        return view('sellers.show', compact('seller', 'products', 'featured', 'stats', 'bundles', 'badges'));
    }
}
