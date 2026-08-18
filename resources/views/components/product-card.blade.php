@props(['product', 'badge' => null])
@php
    $regularLicenseId = \App\Models\LicenseType::regularId();
    $ownedIds = once(fn () => auth()->check() ? auth()->user()->licenses()->where('status', 'active')->pluck('product_id')->map(fn ($id) => (int) $id)->all() : []);
@endphp
<div class="group overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest transition-all hover:-translate-y-1 hover:shadow-lg">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-video overflow-hidden">
        <x-product-thumb :product="$product" class="h-full w-full transition-transform duration-500 group-hover:scale-105" />
        @if($badge)
            <span class="absolute right-4 top-4 rounded-full bg-surface/90 px-3 py-1 font-mono text-[11px] font-semibold tracking-widest text-primary shadow-sm backdrop-blur-sm">{{ $badge }}</span>
        @endif
        @if($product->category)
            <span class="absolute left-4 top-4 rounded-full bg-surface/90 px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-wider text-on-surface backdrop-blur-sm">{{ $product->category->name }}</span>
        @endif
        <span class="absolute inset-x-0 bottom-0 hidden bg-gradient-to-t from-black/50 to-transparent p-3 opacity-0 transition-opacity group-hover:opacity-100 sm:block">
            <span class="inline-flex items-center gap-1 rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold text-on-surface">View details <span class="material-symbols-outlined text-[14px]">arrow_forward</span></span>
        </span>
    </a>
    <div class="p-4">
        <a href="{{ route('products.show', $product->slug) }}">
            <h4 class="truncate text-[17px] font-semibold text-on-surface transition-colors group-hover:text-primary">{{ $product->title }}</h4>
        </a>
        <div class="mt-2 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-container-highest text-[11px] font-bold text-primary">{{ str($product->seller->sellerProfile?->display_name ?? $product->seller->name)->substr(0, 1)->upper() }}</span>
            <a href="{{ route('sellers.show', $product->seller->sellerProfile?->username ?? '#') }}" class="truncate text-sm text-on-surface-variant hover:text-primary">{{ $product->seller->sellerProfile?->display_name ?? $product->seller->name }}</a>
            @if($product->seller->sellerProfile?->status?->value === 'approved')
                <span class="material-symbols-outlined icon-fill text-[15px] text-secondary-fixed-dim">verified</span>
            @endif
        </div>
        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <x-rating-stars :rating="$product->average_rating" :size="16" />
                <span class="font-mono text-xs text-on-surface-variant">{{ number_format((float) $product->average_rating, 1) }}</span>
            </div>
            <span class="font-display text-xl font-bold text-on-surface">${{ number_format((float) $product->regular_price, 2) }}</span>
        </div>
        <div class="mt-4 flex items-center justify-between gap-2 border-t border-outline-variant pt-3">
            <span class="font-mono text-xs uppercase tracking-wider text-on-surface-variant">{{ number_format($product->sales_count) }} sales</span>
            <div class="flex items-center gap-1">
                @auth
                    @if($regularLicenseId && $product->seller_id !== auth()->id())
                        <form method="POST" action="{{ route('cart.add', $product) }}">
                            @csrf
                            <input type="hidden" name="license_type_id" value="{{ $regularLicenseId }}">
                            <button class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-on-primary transition-all hover:opacity-90" aria-label="{{ in_array((int) $product->id, $ownedIds, true) ? 'Buy another license' : 'Add to cart' }}">{{ in_array((int) $product->id, $ownedIds, true) ? 'Buy another' : 'Add' }}</button>
                        </form>
                    @endif
                    @if(in_array((int) $product->id, $ownedIds, true))
                        <a href="{{ route('downloads.index') }}" class="rounded-lg p-1.5 text-primary transition-colors hover:bg-primary/10" aria-label="Download">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                        @csrf
                        <button class="rounded-lg p-1.5 text-primary transition-colors hover:bg-primary/10" aria-label="Add to wishlist">
                            <span class="material-symbols-outlined text-[20px]">favorite</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-on-primary transition-all hover:opacity-90">Buy</a>
                    <a href="{{ route('login') }}" class="rounded-lg p-1.5 text-primary transition-colors hover:bg-primary/10" aria-label="Sign in to save">
                        <span class="material-symbols-outlined text-[20px]">favorite</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>
