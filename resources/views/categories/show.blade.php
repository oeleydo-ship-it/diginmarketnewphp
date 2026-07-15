<x-marketplace-layout :title="$category->name . ' — DiginMarket'" :description="$category->description">
<div class="mx-auto max-w-7xl px-6 py-12">
    <header class="mb-10 flex items-start gap-5">
        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary-container/30 text-primary">
            <span class="material-symbols-outlined text-[34px]">{{ $category->icon ?: 'category' }}</span>
        </span>
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Category</p>
            <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">{{ $category->name }}</h1>
            <p class="mt-3 max-w-2xl text-on-surface-variant">{{ $category->description ?? 'Explore reviewed products from independent sellers.' }}</p>
        </div>
    </header>
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
