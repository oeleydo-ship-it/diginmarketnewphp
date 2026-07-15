<x-marketplace-layout :title="$category->name . ' — DiginMarket'" :description="$category->description">
<div class="mx-auto max-w-7xl px-6 py-12">
    @if($category->imageUrl())
        <header class="relative mb-10 overflow-hidden rounded-2xl">
            <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="h-56 w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/40 to-black/10"></div>
            <div class="absolute inset-x-0 bottom-0 p-8">
                <p class="font-mono text-xs font-medium uppercase tracking-wider text-white/80">Category</p>
                <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight text-white">{{ $category->name }}</h1>
                <p class="mt-2 max-w-2xl text-white/85">{{ $category->description ?? 'Explore reviewed products from independent sellers.' }}</p>
            </div>
        </header>
    @else
        <header class="mb-10">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Category</p>
            <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">{{ $category->name }}</h1>
            <p class="mt-3 max-w-2xl text-on-surface-variant">{{ $category->description ?? 'Explore reviewed products from independent sellers.' }}</p>
        </header>
    @endif
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">category</span>
                <p class="text-on-surface-variant">No published products in this category.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
