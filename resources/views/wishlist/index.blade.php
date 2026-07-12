<x-marketplace-layout title="My Wishlist — DiginMarket">
<x-customer-panel>
    <header class="mb-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">My Wishlist</h1>
        <p class="mt-1 text-on-surface-variant">Assets you saved to compare later.</p>
    </header>
    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">favorite</span>
                <p class="text-on-surface-variant">Save products here to compare them later.</p>
                <a href="{{ route('products.index') }}" class="mt-4 inline-block rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90">Browse assets</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</x-customer-panel>
</x-marketplace-layout>
