<x-marketplace-layout :title="$seller->display_name . ' — DiginMarket'" :description="$seller->biography">
<div class="border-b border-outline-variant bg-surface-container-low">
    <div class="mx-auto max-w-7xl px-6 py-14">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <div class="flex items-start gap-5">
                <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-primary-container font-display text-3xl font-bold text-on-primary-container">{{ str($seller->display_name)->substr(0, 1)->upper() }}</span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="flex items-center gap-1 font-mono text-xs font-medium uppercase tracking-wider text-secondary">
                            <span class="material-symbols-outlined text-[16px]">verified</span> Verified Seller
                        </p>
                        @if($seller->is_featured)
                            <span class="flex items-center gap-1 rounded-full bg-primary px-2.5 py-0.5 font-mono text-[11px] font-bold uppercase tracking-wider text-on-primary">
                                <span class="material-symbols-outlined text-[14px]">star</span> Featured Author
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">{{ $seller->display_name }}</h1>
                    @if($seller->business_name)
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-medium text-on-surface-variant">
                            <span class="material-symbols-outlined text-[16px] text-secondary">workspace_premium</span>
                            {{ $seller->business_name }} · Verified business
                        </p>
                    @endif
                    <p class="mt-3 max-w-2xl text-on-surface-variant">{{ $seller->biography }}</p>
                    <p class="mt-3 text-sm text-on-surface-variant">
                        {{ $seller->country }} · Member since <span class="font-mono text-xs uppercase">{{ $seller->created_at->format('Y') }}</span>
                    </p>
                </div>
            </div>
            @auth
                @if(auth()->id() !== $seller->user_id)
                    <form method="POST" action="{{ route('sellers.follow', $seller) }}">
                        @csrf
                        <button class="flex items-center gap-2 rounded-xl border border-primary px-5 py-3 font-semibold text-primary transition-all hover:bg-primary hover:text-on-primary active:scale-95">
                            <span class="material-symbols-outlined text-[20px]">person_add</span>
                            Follow seller
                        </button>
                    </form>
                @endif
            @endauth
        </div>
        <!-- Author stats -->
        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @php($statItems = [
                ['label' => 'Rating', 'value' => $stats['rating_count'] ? number_format($stats['rating'], 1) : '—', 'sub' => $stats['rating_count'].' '.\Illuminate\Support\Str::plural('review', $stats['rating_count']), 'icon' => 'star'],
                ['label' => 'Total sales', 'value' => number_format($stats['sales']), 'sub' => 'all time', 'icon' => 'shopping_bag'],
                ['label' => 'Products', 'value' => number_format($stats['products']), 'sub' => 'published', 'icon' => 'inventory_2'],
                ['label' => 'Followers', 'value' => number_format($stats['followers']), 'sub' => 'people', 'icon' => 'group'],
                ['label' => 'Following', 'value' => number_format($stats['following']), 'sub' => 'authors', 'icon' => 'person_add'],
                ['label' => 'Member', 'value' => $seller->created_at->format('Y'), 'sub' => 'since', 'icon' => 'calendar_month'],
            ])
            @foreach($statItems as $stat)
                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-4">
                    <span class="material-symbols-outlined text-[20px] text-primary">{{ $stat['icon'] }}</span>
                    <p class="mt-1 font-display text-2xl font-bold text-on-surface">{{ $stat['value'] }}</p>
                    <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">{{ $stat['label'] }}</p>
                    <p class="font-mono text-[11px] text-on-surface-variant opacity-70">{{ $stat['sub'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
<div class="mx-auto max-w-7xl px-6 py-12">
    @if($featured->isNotEmpty())
        <section class="mb-12">
            <h2 class="mb-6 flex items-center gap-2 font-display text-2xl font-semibold tracking-tight">
                <span class="material-symbols-outlined text-primary">star</span> Featured products
            </h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($featured as $product)
                    <x-product-card :product="$product" badge="Featured" />
                @endforeach
            </div>
        </section>
    @endif
    @if($bundles->isNotEmpty())
        <section class="mb-12">
            <h2 class="mb-6 flex items-center gap-2 font-display text-2xl font-semibold tracking-tight">
                <span class="material-symbols-outlined text-primary">package_2</span> Bundles
            </h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($bundles as $bundle)
                    @php($compareAt = $bundle->products->sum(fn ($p) => (float) $p->regular_price))
                    @php($saving = $compareAt > (float) $bundle->price ? round((1 - (float) $bundle->price / $compareAt) * 100) : 0)
                    <a href="{{ route('bundles.show', $bundle->slug) }}" class="group rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-lg">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-display font-bold leading-snug group-hover:text-primary">{{ $bundle->title }}</h3>
                            @if($saving > 0)<span class="shrink-0 rounded-full bg-secondary-container/40 px-2 py-0.5 text-xs font-bold text-on-secondary-container">-{{ $saving }}%</span>@endif
                        </div>
                        <p class="mt-2 text-sm text-on-surface-variant">{{ $bundle->products_count }} products</p>
                        <p class="mt-3 font-display text-xl font-bold text-primary">${{ number_format((float) $bundle->price, 0) }}
                            @if($saving > 0)<span class="ml-1 text-sm font-medium text-on-surface-variant line-through">${{ number_format($compareAt, 0) }}</span>@endif
                        </p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <h2 class="mb-6 font-display text-2xl font-semibold tracking-tight">All products</h2>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <p class="col-span-full text-on-surface-variant">No published products yet.</p>
        @endforelse
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
