@props(['product'])
<div class="group flex flex-col gap-5 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 transition-all hover:border-primary/40 hover:shadow-md sm:flex-row sm:items-center">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-video shrink-0 overflow-hidden rounded-xl sm:h-36 sm:w-52">
        <x-product-thumb :product="$product" class="h-full w-full transition-transform duration-500 group-hover:scale-105" />
    </a>
    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('products.show', $product->slug) }}">
                    <h4 class="truncate text-xl font-semibold text-on-surface transition-colors group-hover:text-primary">{{ $product->title }}</h4>
                </a>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-on-surface-variant">
                    <span class="flex items-center gap-1.5">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-surface-container-highest text-[10px] font-bold text-primary">{{ str($product->seller->sellerProfile?->display_name ?? $product->seller->name)->substr(0, 1)->upper() }}</span>
                        <a href="{{ route('sellers.show', $product->seller->sellerProfile?->username ?? '#') }}" class="hover:text-primary">{{ $product->seller->sellerProfile?->display_name ?? $product->seller->name }}</a>
                        @if($product->seller->sellerProfile?->status?->value === 'approved')
                            <span class="material-symbols-outlined icon-fill text-[14px] text-secondary-fixed-dim">verified</span>
                        @endif
                    </span>
                    <span class="text-outline-variant">·</span>
                    <span class="rounded bg-surface-container-high px-2 py-0.5 font-mono text-[11px] uppercase tracking-wide">{{ $product->category->name }}</span>
                </div>
                @if($product->short_description)
                    <p class="mt-2.5 line-clamp-2 text-sm text-on-surface-variant">{{ $product->short_description }}</p>
                @endif
            </div>
            <div class="shrink-0 text-right">
                <span class="font-display text-2xl font-bold text-on-surface">${{ number_format((float) $product->regular_price, 2) }}</span>
                <div class="mt-1 flex items-center justify-end gap-1.5">
                    <x-rating-stars :rating="$product->average_rating" :size="14" />
                    <span class="font-mono text-xs text-on-surface-variant">{{ number_format((float) $product->average_rating, 1) }}</span>
                </div>
                <span class="mt-1 block font-mono text-[11px] uppercase tracking-wider text-on-surface-variant">{{ number_format($product->sales_count) }} sales</span>
            </div>
        </div>
    </div>
    <div class="flex shrink-0 items-center gap-2 sm:flex-col">
        @auth
            @php
                $regularLicenseId = \App\Models\LicenseType::regularId();
                $ownsThis = in_array((int) $product->id, once(fn () => auth()->user()->licenses()->where('status', 'active')->pluck('product_id')->map(fn ($id) => (int) $id)->all()), true);
            @endphp
            @if($regularLicenseId && $product->seller_id !== auth()->id())
                <form method="POST" action="{{ route('cart.add', $product) }}">
                    @csrf
                    <input type="hidden" name="license_type_id" value="{{ $regularLicenseId }}">
                    <button class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">{{ $ownsThis ? 'Buy another license' : 'Add to cart' }}</button>
                </form>
                @if($ownsThis)
                    <a href="{{ route('downloads.index') }}" class="flex items-center justify-center gap-1.5 rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-variant transition-all hover:border-primary hover:text-primary">Download</a>
                @endif
            @else
                <a href="{{ route('products.show', $product->slug) }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
                    View <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            @endif
        @else
            <a href="{{ route('products.show', $product->slug) }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
                View <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        @endauth
        @auth
            <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                @csrf
                <button class="flex items-center justify-center rounded-lg border border-outline-variant p-2 text-primary transition-colors hover:bg-primary/10" aria-label="Add to wishlist">
                    <span class="material-symbols-outlined text-[20px]">favorite</span>
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="flex items-center justify-center rounded-lg border border-outline-variant p-2 text-primary transition-colors hover:bg-primary/10" aria-label="Sign in to save">
                <span class="material-symbols-outlined text-[20px]">favorite</span>
            </a>
        @endauth
    </div>
</div>
