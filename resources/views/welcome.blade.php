<x-nexus-layout :title="$homepage['meta_title']" :description="$homepage['meta_description']">

<!-- Hero -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-primary/10 via-surface to-surface"></div>
    <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 rounded-full bg-primary/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-16 bottom-0 h-80 w-80 rounded-full bg-secondary/15 blur-3xl"></div>
    <div class="relative z-10 mx-auto max-w-7xl px-6 pb-16 pt-16 text-center sm:pt-20">
        @if(!empty($homepage['settings']['hero_badge_text']))
        <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary">
            <span class="material-symbols-outlined text-[16px]">verified</span> {{ $homepage['settings']['hero_badge_text'] }}
        </p>
        @endif
        <h1 class="mx-auto max-w-4xl font-display text-4xl font-bold leading-tight tracking-tight text-on-surface sm:text-5xl sm:leading-[1.15]">{{ $homepage['title'] }}</h1>
        @if(!empty($homepage['excerpt']))
        <p class="mx-auto mt-6 max-w-2xl text-lg font-medium text-on-surface-variant">{{ $homepage['excerpt'] }}</p>
        @endif
        @if(!empty($homepage['settings']['cta_primary_text']) || !empty($homepage['settings']['cta_secondary_text']))
        <div class="mx-auto mt-8 flex max-w-3xl flex-col items-center justify-center gap-4 sm:flex-row">
            @if(!empty($homepage['settings']['cta_primary_text']) && !empty($homepage['settings']['cta_primary_url']))
            <a href="{{ $homepage['settings']['cta_primary_url'] }}" class="rounded-2xl bg-primary px-8 py-3 text-base font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">{{ $homepage['settings']['cta_primary_text'] }}</a>
            @endif
            @if(!empty($homepage['settings']['cta_secondary_text']) && !empty($homepage['settings']['cta_secondary_url']))
            <a href="{{ $homepage['settings']['cta_secondary_url'] }}" class="rounded-2xl border border-outline-variant bg-surface-container-lowest px-8 py-3 text-base font-semibold text-on-surface transition-colors hover:border-primary hover:text-primary">{{ $homepage['settings']['cta_secondary_text'] }}</a>
            @endif
        </div>
        @endif
        <form action="{{ route('products.index') }}" method="GET" data-live-search data-suggest-url="{{ route('products.suggest') }}" class="relative mx-auto mt-10 max-w-3xl">
            <div class="flex rounded-2xl border border-outline-variant bg-surface-container-lowest p-1.5 shadow-lg ring-primary transition-all focus-within:ring-2">
                <div class="flex items-center pl-4 text-on-surface-variant">
                    <span class="material-symbols-outlined">search</span>
                </div>
                <input name="q" type="search" autocomplete="off" placeholder="Search scripts, themes, and more..."
                    class="w-full border-none bg-transparent px-4 py-4 text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-0">
                <button class="shrink-0 rounded-xl bg-primary px-8 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Search</button>
            </div>
            <div data-search-results hidden class="absolute inset-x-0 top-full z-20 mt-2 overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-xl"></div>
        </form>
        <div class="mx-auto mt-5 flex max-w-3xl flex-wrap items-center justify-center gap-2 text-sm">
            <span class="text-on-surface-variant">Popular:</span>
            @foreach(['Laravel', 'WordPress', 'UI Kit', 'Dashboard'] as $chip)
                <a href="{{ route('products.index', ['q' => $chip]) }}" class="rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-on-surface-variant transition-colors hover:border-primary hover:text-primary">{{ $chip }}</a>
            @endforeach
        </div>
    </div>
</section>

@if(($stats['products'] ?? 0) > 0)
<section class="border-y border-outline-variant bg-surface-container-lowest">
    <div class="mx-auto grid max-w-7xl grid-cols-3 divide-x divide-outline-variant px-6 py-8 text-center">
        <div>
            <p class="font-display text-2xl font-bold text-primary sm:text-3xl">{{ number_format($stats['products']) }}+</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-on-surface-variant sm:text-sm">Assets</p>
        </div>
        <div>
            <p class="font-display text-2xl font-bold text-primary sm:text-3xl">{{ number_format($stats['creators']) }}+</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-on-surface-variant sm:text-sm">Creators</p>
        </div>
        <div>
            <p class="font-display text-2xl font-bold text-primary sm:text-3xl">{{ number_format($stats['sales']) }}+</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-on-surface-variant sm:text-sm">Sales</p>
        </div>
    </div>
</section>
@endif

@if(!empty($homepage['body']))
<section class="mx-auto max-w-4xl px-6 py-12">
    <x-rich-content :html="$homepage['body']" class="max-w-none text-[15px] leading-relaxed text-on-surface" />
</section>
@endif

<!-- Categories -->
@if($homepage['settings']['show_categories'])
<section id="categories" class="mx-auto max-w-7xl px-6 py-16">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <h2 class="font-display text-3xl font-semibold tracking-tight">{{ $homepage['settings']['categories_title'] }}</h2>
            <p class="mt-1 text-on-surface-variant">{{ $homepage['settings']['categories_subtitle'] }}</p>
        </div>
        <a href="{{ route('products.index') }}" class="hidden items-center gap-1 font-semibold text-primary hover:underline sm:flex">
            All assets <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>
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
</section>
@endif

<!-- How it works -->
<section class="bg-surface-container-low py-16">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-10 text-center">
            <h2 class="font-display text-3xl font-semibold tracking-tight">How DiginMarket works</h2>
            <p class="mt-2 text-on-surface-variant">Licensed files, instant downloads, and seller support in three steps.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach([
                ['search', 'Discover', 'Browse reviewed scripts, themes, and kits with filters for price, rating, and license.'],
                ['verified_user', 'License', 'Buy a regular or business license. Checkout is priced on the server — never from the browser.'],
                ['download', 'Download', 'Get instant access, updates you are entitled to, and support from the original creator.'],
            ] as $i => $step)
                <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
                    <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <span class="material-symbols-outlined">{{ $step[0] }}</span>
                    </span>
                    <p class="font-mono text-xs font-semibold uppercase tracking-widest text-primary">Step {{ $i + 1 }}</p>
                    <h3 class="mt-1 text-lg font-semibold">{{ $step[1] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-on-surface-variant">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Trending Products -->
@if($homepage['settings']['show_trending'])
<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <h2 class="font-display text-3xl font-semibold tracking-tight">{{ $homepage['settings']['trending_title'] }}</h2>
            <p class="mt-1 text-on-surface-variant">{{ $homepage['settings']['trending_subtitle'] }}</p>
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
</section>
@endif

<!-- New Arrivals -->
@if($homepage['settings']['show_new_arrivals'])
<section class="bg-surface-container-low py-16">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h2 class="font-display text-3xl font-semibold tracking-tight">{{ $homepage['settings']['new_arrivals_title'] }}</h2>
                <p class="mt-1 text-on-surface-variant">{{ $homepage['settings']['new_arrivals_subtitle'] }}</p>
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
    </div>
</section>
@endif

<!-- Staff Picks -->
@if($featured->isNotEmpty())
<section class="mx-auto max-w-7xl px-6 py-16">
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

@if($homepage['settings']['show_featured_creators'] && $authors->isNotEmpty())
<section class="bg-surface-container-low py-16">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h2 class="font-display text-3xl font-semibold tracking-tight">{{ $homepage['settings']['featured_creators_title'] }}</h2>
                <p class="mt-1 text-on-surface-variant">{{ $homepage['settings']['featured_creators_subtitle'] }}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach($authors as $author)
                <a href="{{ route('sellers.show', $author->username) }}" class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-container font-display text-lg font-bold text-on-primary-container">{{ str($author->display_name)->substr(0, 1)->upper() }}</span>
                    <h3 class="mt-3 truncate font-semibold">{{ $author->display_name }}</h3>
                    <p class="mt-1 font-mono text-xs text-on-surface-variant">{{ number_format((int) $author->published_products_count) }} {{ \Illuminate\Support\Str::plural('product', (int) $author->published_products_count) }}</p>
                    @if($author->is_featured)
                        <span class="mt-2 inline-block rounded bg-secondary-container/40 px-2 py-0.5 text-[10px] font-bold uppercase text-on-secondary-container">Featured</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($recentlyViewed->isNotEmpty())
<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <h2 class="font-display text-3xl font-semibold tracking-tight">Recently viewed</h2>
            <p class="mt-1 text-on-surface-variant">Pick up where you left off.</p>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        @foreach($recentlyViewed->take(4) as $product)
            <x-product-card :product="$product" />
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
