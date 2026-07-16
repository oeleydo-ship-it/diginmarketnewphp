<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SellerBundleController extends Controller
{
    public function index(): View
    {
        $bundles = Bundle::where('seller_id', auth()->id())->withCount('products')->latest()->get();
        $products = Product::where('seller_id', auth()->id())->where('status', 'published')->orderBy('title')->get(['id', 'title', 'regular_price']);

        return view('seller.bundles.index', compact('bundles', 'products'));
    }

    public function store(): RedirectResponse
    {
        $data = $this->validated();
        $bundle = Bundle::create(['seller_id' => auth()->id(), 'title' => $data['title'], 'slug' => str($data['title'])->slug().'-'.str()->lower(str()->random(6)), 'description' => $data['description'] ?? null, 'price' => $data['price'], 'is_active' => true]);
        $bundle->products()->sync($data['product_ids']);

        return back()->with('status', 'Bundle created.');
    }

    public function update(Bundle $bundle): RedirectResponse
    {
        abort_unless($bundle->seller_id === auth()->id(), 403);
        $data = $this->validated();
        $bundle->update(['title' => $data['title'], 'description' => $data['description'] ?? null, 'price' => $data['price'], 'is_active' => (bool) request()->boolean('is_active')]);
        $bundle->products()->sync($data['product_ids']);

        return back()->with('status', 'Bundle updated.');
    }

    public function destroy(Bundle $bundle): RedirectResponse
    {
        abort_unless($bundle->seller_id === auth()->id(), 403);
        $bundle->delete();

        return back()->with('status', 'Bundle deleted.');
    }

    private function validated(): array
    {
        $data = request()->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'product_ids' => ['required', 'array', 'min:2'],
            'product_ids.*' => ['integer'],
        ]);
        // Only the seller's own published products can enter a bundle — anything else 422s.
        $owned = Product::where('seller_id', auth()->id())->where('status', 'published')->whereIn('id', $data['product_ids'])->pluck('id');
        abort_unless($owned->count() === count(array_unique($data['product_ids'])), 422, 'Bundles may only contain your own published products.');

        return $data;
    }
}
