<x-nexus-layout title="DiginMarket — Premium Digital Assets" description="Access a curated library of high-quality scripts, themes, and design tools from top-tier creators.">

<!-- Hero -->
<section class="relative flex min-h-[520px] items-center justify-center overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-surface-container-low via-surface to-surface"></div>
    <div class="absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 2px 2px, #c7c4d8 1px, transparent 0); background-size: 28px 28px;"></div>
    <div class="relative z-10 mx-auto max-w-7xl px-6 py-20 text-center">
        <h1 class="mx-auto max-w-4xl font-display text-4xl font-bold leading-tight tracking-tight text-on-surface sm:text-5xl sm:leading-[1.15]">Find the perfect digital assets for your next project.</h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg font-medium text-on-surface-variant">Access a curated library of high-quality scripts, themes, and design tools from top-tier creators worldwide.</p>
        <form action="{{ route('products.index') }}" method="GET" class="mx-auto mt-10 flex max-w-3xl rounded-2xl border border-outline-variant bg-surface-container-lowest p-1.5 shadow-lg ring-primary transition-all focus-within:ring-2">
            <div class="flex items-center pl-4 text-on-surface-variant">
                <span class="material-symbols-outlined">search</span>
            </div>
            <input name="q" type="text" placeholder="Search scripts, themes, and more..."
                class="w-full border-none bg-transparent px-4 py-4 text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-0">
            <button class="shrink-0 rounded-xl bg-primary px-8 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Search</button>
        </form>
    </div>
</section>

<!-- Categories Bento -->
<section id="categories" class="mx-auto max-w-7xl px-6 py-16">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <h2 class="font-display text-3xl font-semibold tracking-tight">Browse Categories</h2>
            <p class="mt-1 text-on-surface-variant">Find the right tools by type.</p>
        </div>
        <a href="{{ route('products.index') }}" class="hidden items-center gap-1 font-semibold text-primary hover:underline sm:flex">
            All assets <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @foreach($categories as $category)
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
        @endforeach
    </div>
</section>

<!-- Trending Products -->
<section class="bg-surface-container-low py-16">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h2 class="font-display text-3xl font-semibold tracking-tight">Trending Products</h2>
                <p class="mt-1 text-on-surface-variant">The most popular assets this week.</p>
            </div>
            <a href="{{ route('products.index', ['sort' => 'popular']) }}" class="flex items-center gap-1 font-semibold text-primary hover:underline">
                View All <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
        @php $trendingList = $trending->isNotEmpty() ? $trending : $bestSellers; @endphp
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            @forelse($trendingList->take(4) as $product)
                <x-product-card :product="$product" :badge="$loop->first ? 'HOT' : null" />
            @empty
                <p class="text-on-surface-variant">Approved products will appear here.</p>
            @endforelse
        </div>
    </div>
</section>

<!-- New Arrivals -->
<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <h2 class="font-display text-3xl font-semibold tracking-tight">New Arrivals</h2>
            <p class="mt-1 text-on-surface-variant">Fresh releases from our creator community.</p>
        </div>
        <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="flex items-center gap-1 font-semibold text-primary hover:underline">
            View All <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        @forelse($newest->take(4) as $product)
            <x-product-card :product="$product" :badge="$loop->first ? 'NEW' : null" />
        @empty
            <p class="text-on-surface-variant">Approved products will appear here.</p>
        @endforelse
    </div>
</section>

<!-- Staff Picks -->
@if($featured->isNotEmpty())
<section class="mx-auto max-w-7xl px-6 pb-16">
    <h2 class="mb-8 font-display text-3xl font-semibold tracking-tight">Staff Picks</h2>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach($featured->take(2) as $product)
            <div class="flex h-auto flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest transition-all hover:shadow-xl md:h-64 md:flex-row">
                <a href="{{ route('products.show', $product->slug) }}" class="block w-full overflow-hidden md:w-1/2">
                    <x-product-thumb :product="$product" class="h-48 w-full md:h-full" />
                </a>
                <div class="flex w-full flex-col justify-between p-6 md:w-1/2">
                    <div>
                        <span class="mb-2 inline-block rounded bg-secondary-container/25 px-2 py-1 font-mono text-[11px] font-semibold tracking-widest text-secondary">STAFF CHOICE</span>
                        <a href="{{ route('products.show', $product->slug) }}">
                            <h3 class="mb-1 text-[17px] font-semibold transition-colors hover:text-primary">{{ $product->title }}</h3>
                        </a>
                        <p class="line-clamp-2 text-sm text-on-surface-variant">{{ $product->short_description }}</p>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="font-display text-2xl font-bold">${{ number_format((float) $product->regular_price, 2) }}</span>
                        <a href="{{ route('products.show', $product->slug) }}" class="rounded-lg bg-surface-container-high px-4 py-2 text-sm font-semibold transition-colors hover:bg-surface-container-highest">Preview</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

<!-- CTA -->
<section class="mx-auto max-w-7xl px-6 pb-4">
    <div class="relative flex flex-col items-center overflow-hidden rounded-3xl bg-primary p-12 text-center text-on-primary sm:p-16">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
        <h2 class="relative z-10 mb-4 font-display text-4xl font-bold tracking-tight">Start selling on DiginMarket today.</h2>
        <p class="relative z-10 mb-8 max-w-2xl text-lg opacity-90">Join a growing community of creators and reach buyers worldwide with our powerful marketplace engine.</p>
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row">
            <a href="{{ auth()->check() ? route('seller.apply') : (\App\Models\Setting::enabled('features.registration') ? route('register') : route('login')) }}" class="rounded-2xl bg-on-primary px-10 py-4 text-lg font-semibold text-primary transition-all hover:bg-surface-container-lowest active:scale-95">Become a Seller</a>
            <a href="{{ route('products.index') }}" class="rounded-2xl border-2 border-on-primary px-10 py-4 text-lg font-semibold text-on-primary transition-all hover:bg-white/10 active:scale-95">Explore Assets</a>
        </div>
    </div>
</section>

</x-nexus-layout>
