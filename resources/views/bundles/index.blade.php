<x-marketplace-layout title="Product Bundles — DiginMarket" description="Curated product bundles from independent creators — multiple licenses at one discounted price.">
<div class="mx-auto max-w-7xl px-6 py-14">
    <div class="mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Better together</p>
            <h1 class="mt-1 font-display text-4xl font-bold tracking-tight">Product bundles</h1>
            <p class="mt-3 max-w-xl text-on-surface-variant">Multiple products from one creator at a single discounted price — each item ships with its own license and downloads.</p>
        </div>
        <form method="GET" class="relative">
            <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-on-surface-variant">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="Search bundles..." aria-label="Search bundles"
                class="w-full rounded-xl border border-outline-variant bg-surface-container-low py-2.5 pl-10 pr-4 text-sm md:w-72 focus:outline-none focus:ring-2 focus:ring-primary/20">
        </form>
    </div>

    @if($bundles->isEmpty())
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest py-24 text-center">
            <span class="material-symbols-outlined mb-3 block text-5xl text-on-surface-variant">package_2</span>
            <p class="font-display text-lg font-semibold">No bundles {{ request('q') ? 'match your search' : 'yet' }}</p>
            <p class="mt-1 text-sm text-on-surface-variant">{{ request('q') ? 'Try a different keyword.' : 'Sellers are putting collections together — check back soon.' }}</p>
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($bundles as $bundle)
                @php($compareAt = $bundle->products->sum(fn ($p) => (float) $p->regular_price))
                @php($saving = $compareAt > (float) $bundle->price ? round((1 - (float) $bundle->price / $compareAt) * 100) : 0)
                <a href="{{ route('bundles.show', $bundle->slug) }}" class="group flex flex-col rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-container/30 text-primary"><span class="material-symbols-outlined">package_2</span></span>
                        @if($saving > 0)<span class="rounded-full bg-secondary-container/40 px-2.5 py-1 text-xs font-bold text-on-secondary-container">Save {{ $saving }}%</span>@endif
                    </div>
                    <h2 class="mt-4 font-display text-lg font-bold leading-snug group-hover:text-primary">{{ $bundle->title }}</h2>
                    <p class="mt-1 text-xs text-on-surface-variant">by {{ $bundle->seller->sellerProfile?->display_name ?? $bundle->seller->name }}</p>
                    <ul class="mt-4 flex-1 space-y-1.5">
                        @foreach($bundle->products->take(3) as $product)
                            <li class="flex items-center gap-2 text-sm text-on-surface-variant"><span class="material-symbols-outlined text-[16px] text-secondary">check</span><span class="truncate">{{ $product->title }}</span></li>
                        @endforeach
                        @if($bundle->products_count > 3)<li class="text-xs font-semibold text-on-surface-variant">+ {{ $bundle->products_count - 3 }} more</li>@endif
                    </ul>
                    <div class="mt-5 flex items-baseline justify-between border-t border-outline-variant pt-4">
                        <div>
                            <span class="font-display text-2xl font-bold text-primary">${{ number_format((float) $bundle->price, 0) }}</span>
                            @if($saving > 0)<span class="ml-2 text-sm text-on-surface-variant line-through">${{ number_format($compareAt, 0) }}</span>@endif
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">{{ $bundle->products_count }} products</span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10">{{ $bundles->onEachSide(1)->links() }}</div>
    @endif
</div>
</x-marketplace-layout>
