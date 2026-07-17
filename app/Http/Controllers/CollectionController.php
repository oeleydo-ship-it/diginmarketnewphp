<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CollectionController extends Controller
{
    public function index(): View
    {
        $collections = ProductCollection::where('user_id', auth()->id())->withCount('products')->latest()->get();

        return view('collections.index', compact('collections'));
    }

    public function show(string $slug): View
    {
        $collection = ProductCollection::where('slug', $slug)->withCount('products')->firstOrFail();
        abort_unless($collection->is_public || $collection->user_id === auth()->id(), 404);
        $products = $collection->products()->where('status', 'published')->with(['category', 'seller.sellerProfile'])->paginate(12);

        return view('collections.show', compact('collection', 'products'));
    }

    public function store(): RedirectResponse
    {
        $data = request()->validate(['title' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'is_public' => ['nullable', 'boolean']]);
        ProductCollection::create(['user_id' => auth()->id(), 'title' => $data['title'], 'slug' => str($data['title'])->slug().'-'.str()->lower(str()->random(6)), 'description' => $data['description'] ?? null, 'is_public' => request()->boolean('is_public', true)]);

        return back()->with('status', 'Collection created.');
    }

    /** Add a product to one of the buyer's collections — optionally creating the collection inline. */
    public function add(Product $product): RedirectResponse
    {
        abort_unless($product->status->value === 'published', 404);
        $data = request()->validate(['collection_id' => ['nullable', 'integer'], 'new_title' => ['nullable', 'string', 'max:120']]);
        if (filled($data['new_title'] ?? null)) {
            $collection = ProductCollection::create(['user_id' => auth()->id(), 'title' => $data['new_title'], 'slug' => str($data['new_title'])->slug().'-'.str()->lower(str()->random(6)), 'is_public' => true]);
        } else {
            $collection = ProductCollection::where('user_id', auth()->id())->findOrFail($data['collection_id'] ?? 0);
        }
        $collection->products()->syncWithoutDetaching([$product->id]);

        return back()->with('status', 'Added to "'.$collection->title.'".');
    }

    public function remove(ProductCollection $collection, Product $product): RedirectResponse
    {
        abort_unless($collection->user_id === auth()->id(), 403);
        $collection->products()->detach($product->id);

        return back()->with('status', 'Removed from collection.');
    }

    public function destroy(ProductCollection $collection): RedirectResponse
    {
        abort_unless($collection->user_id === auth()->id(), 403);
        $collection->delete();

        return redirect()->route('collections.index')->with('status', 'Collection deleted.');
    }
}
