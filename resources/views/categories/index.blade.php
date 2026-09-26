<x-marketplace-layout title="Categories — DiginMarket" description="Browse all digital product categories on DiginMarket.">
<div class="mx-auto max-w-7xl px-4 py-10 md:px-6 lg:py-14">
    <header class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Browse</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">All Categories</h1>
            <p class="mt-2 text-on-surface-variant">Find the right tools by type.</p>
        </div>
        <a href="{{ route('products.index') }}"
            class="inline-flex items-center gap-1 font-semibold text-primary hover:underline">
            All assets <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </header>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @forelse($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}"
                class="group flex flex-col items-center justify-center gap-3 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 text-center transition-all hover:-translate-y-1 hover:border-primary/40 hover:shadow-md">
                <span class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-xl bg-primary-container/25 text-primary transition-colors group-hover:bg-primary group-hover:text-on-primary">
                    @if($category->imageUrl())
                        <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="h-full w-full object-cover" loading="lazy">
                    @else
                        <span class="material-symbols-outlined text-[28px]">{{ $category->displayIcon() }}</span>
                    @endif
                </span>
                <div>
                    <h3 class="text-[15px] font-semibold text-on-surface">{{ $category->name }}</h3>
                    <span class="mt-0.5 block font-mono text-xs text-on-surface-variant">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('product', $category->products_count) }}</span>
                </div>
            </a>
        @empty
            <p class="col-span-full text-on-surface-variant">Categories will appear here once the catalog is ready.</p>
        @endforelse
    </div>
</div>
</x-marketplace-layout>
