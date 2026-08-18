@props(['product', 'license' => null, 'compact' => false])
@if($product->offersSupportAddon())
    @php($months = $product->supportExtensionMonths())
    @php($price = number_format((float) $product->support_extension_price, 2))
    <div {{ $attributes->class($compact ? 'rounded-xl border border-primary/25 bg-primary/5 p-4' : 'rounded-2xl border border-primary/25 bg-primary/5 p-5 shadow-sm') }}>
        <p class="font-mono text-[10px] font-bold uppercase tracking-wider text-primary">Addon</p>
        <h3 class="mt-1 font-semibold text-on-surface">{{ $months }} months extra updates &amp; support</h3>
        <p class="mt-1 text-sm text-on-surface-variant">Additional payment on your existing license — not a new product license. Stacks another {{ $months }} months of seller updates and support.</p>
        <p class="mt-3 font-display text-2xl font-bold text-primary">${{ $price }}</p>
        @if($license && $license->status === 'active')
            @if($license->support_expires_at)
                <p class="mt-1 text-xs text-on-surface-variant">Current coverage {{ $license->support_expires_at->isFuture() ? 'until '.$license->support_expires_at->toFormattedDateString() : 'expired '.$license->support_expires_at->toFormattedDateString() }} — this addon extends that date.</p>
            @endif
            <form method="POST" action="{{ route('licenses.extend-support', $license) }}" class="mt-4">
                @csrf
                <button class="{{ $compact ? 'rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-on-primary transition-all hover:opacity-90' : 'flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-on-primary shadow-md transition-all hover:brightness-110 active:scale-[0.98]' }}">
                    Buy {{ $months }}-month addon
                </button>
            </form>
        @elseif(auth()->check())
            <p class="mt-3 text-sm text-on-surface-variant">Buy this product first, then you can add extra updates &amp; support on your license.</p>
        @else
            <p class="mt-3 text-sm text-on-surface-variant">Sign in and buy the product first to purchase this addon.</p>
            <a href="{{ route('login') }}" class="mt-3 inline-flex font-semibold text-primary hover:underline">Sign in</a>
        @endif
    </div>
@endif
