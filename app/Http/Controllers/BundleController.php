<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Services\DirectCheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BundleController extends Controller
{
    public function index(): View
    {
        $bundles = Bundle::purchasable()
            ->with(['products' => fn ($q) => $q->with('category'), 'seller.sellerProfile'])
            ->withCount('products')
            ->when(request('q'), fn ($q, $term) => $q->where('title', 'like', '%'.$term.'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('bundles.index', compact('bundles'));
    }

    public function show(string $slug): View
    {
        $bundle = Bundle::where('slug', $slug)->where('is_active', true)->with(['products' => fn ($q) => $q->where('status', 'published')->with('category'), 'seller.sellerProfile'])->firstOrFail();

        return view('bundles.show', ['bundle' => $bundle, 'compareAt' => $bundle->compareAtPrice()]);
    }

    public function buy(Bundle $bundle, DirectCheckoutService $checkout): RedirectResponse
    {
        $result = $checkout->startBundle($bundle, auth()->user(), request('payment_provider'));

        return redirect()->away($result['url']);
    }
}
