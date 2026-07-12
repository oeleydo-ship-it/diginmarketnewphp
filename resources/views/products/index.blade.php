<x-marketplace-layout title="Browse Digital Assets — DiginMarket">
<div class="mx-auto max-w-7xl px-6 py-12">
    <header class="mb-8">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Curated Marketplace</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Find your next building block</h1>
    </header>
    <form method="GET" class="mb-4 grid gap-3 rounded-xl border border-outline-variant bg-surface-container-low p-5 md:grid-cols-6">
        <div class="relative md:col-span-2">
            <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-on-surface-variant">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="Search products"
                class="w-full rounded-lg border border-outline-variant bg-surface py-3 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
        </div>
        <select name="category" class="rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>
        <input name="min_price" type="number" value="{{ request('min_price') }}" placeholder="Min price" class="rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
        <input name="max_price" type="number" value="{{ request('max_price') }}" placeholder="Max price" class="rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
        <select name="sort" class="rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
            <option value="newest">Newest</option>
            <option value="popular" @selected(request('sort') === 'popular')>Best selling</option>
            <option value="rated" @selected(request('sort') === 'rated')>Highest rated</option>
            <option value="price_low" @selected(request('sort') === 'price_low')>Lowest price</option>
            <option value="price_high" @selected(request('sort') === 'price_high')>Highest price</option>
        </select>
        <button class="flex items-center justify-center gap-2 rounded-lg bg-primary p-3 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95 md:col-span-6">
            <span class="material-symbols-outlined text-[18px]">tune</span>
            Apply filters
        </button>
    </form>
    <div class="mb-8 flex flex-wrap gap-2">
        @foreach($categories as $category)
            <a href="{{ route('products.index', array_merge(request()->except('page'), ['category' => request('category') === $category->slug ? null : $category->slug])) }}"
                class="rounded-full border px-4 py-1.5 text-sm font-medium transition-colors {{ request('category') === $category->slug ? 'border-primary bg-primary text-on-primary' : 'border-outline-variant text-on-surface-variant hover:border-primary hover:text-primary' }}">
                {{ $category->name }}
                @if(request('category') === $category->slug)<span class="material-symbols-outlined ml-1 align-middle text-[14px]">close</span>@endif
            </a>
        @endforeach
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">search_off</span>
                <p class="text-on-surface-variant">No products match these filters.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
