<x-marketplace-layout title="Review Order — DiginMarket">
<div class="mx-auto max-w-7xl px-6 py-12">
    <div class="mx-auto mb-14 max-w-3xl">
        <div class="relative flex items-center justify-between">
            <div class="absolute left-0 top-5 -z-10 h-[2px] w-full bg-outline-variant"></div>
            <div class="absolute left-0 top-5 -z-10 h-[2px] w-1/2 bg-primary"></div>
            <div class="flex flex-col items-center gap-2 bg-surface px-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary font-bold text-on-primary"><span class="material-symbols-outlined text-[20px]">check</span></div>
                <span class="text-sm font-semibold text-primary">Cart</span>
            </div>
            <div class="flex flex-col items-center gap-2 bg-surface px-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-primary bg-surface font-bold text-primary">2</div>
                <span class="text-sm font-semibold text-primary">Review</span>
            </div>
            <div class="flex flex-col items-center gap-2 bg-surface px-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-outline-variant font-bold text-on-surface-variant">3</div>
                <span class="text-sm font-semibold text-on-surface-variant">Payment</span>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">
            <section class="rounded-xl border border-outline-variant bg-surface-container-low p-6">
                <div class="mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">shopping_cart</span>
                    <h1 class="font-display text-lg font-semibold">Review Order</h1>
                </div>
                <div class="space-y-4">
                    @forelse($cart->items as $item)
                        <div class="flex flex-col gap-5 rounded-lg border border-outline-variant/50 bg-surface p-4 md:flex-row">
                            <div class="h-28 w-full shrink-0 overflow-hidden rounded-lg bg-surface-container-high md:w-44">
                                <x-product-thumb :product="$item->product" class="h-full w-full" />
                            </div>
                            <div class="flex flex-1 flex-col justify-between gap-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <a href="{{ route('products.show', $item->product->slug) }}" class="font-semibold text-primary hover:underline">{{ $item->product->title }}</a>
                                        <p class="mt-1 flex items-center gap-1 text-sm text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[16px]">verified</span>
                                            {{ $item->product->seller->name }}
                                        </p>
                                    </div>
                                    <span class="font-display text-xl font-bold">${{ number_format($item->total, 2) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <span class="flex items-center gap-1 rounded bg-secondary-container/40 px-2 py-1 font-mono text-[10px] font-medium uppercase tracking-wider text-on-secondary-container">
                                        <span class="material-symbols-outlined text-[14px]">gavel</span>
                                        {{ $item->licenseType->name }}
                                    </span>
                                    <form method="POST" action="{{ route('cart.remove', $item) }}">
                                        @csrf @method('DELETE')
                                        <button class="flex items-center gap-1 text-sm font-medium text-error transition-colors hover:text-on-error-container">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-outline-variant p-12 text-center">
                            <span class="material-symbols-outlined mb-3 text-[40px] text-outline">remove_shopping_cart</span>
                            <p class="text-on-surface-variant">Your cart is empty.</p>
                            <a href="{{ route('products.index') }}" class="mt-4 inline-block rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90">Browse assets</a>
                        </div>
                    @endforelse
                </div>
            </section>
            @if($cart->items->isNotEmpty())
                <div class="flex flex-wrap items-center justify-center gap-10 py-4 opacity-60 md:justify-start">
                    <div class="flex items-center gap-1.5"><span class="material-symbols-outlined text-primary">security</span><span class="font-mono text-xs font-bold tracking-widest">SSL SECURED</span></div>
                    <div class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[#6772e5]">payments</span><span class="font-mono text-xs font-bold tracking-widest">STRIPE VERIFIED</span></div>
                    <div class="flex items-center gap-1.5"><span class="material-symbols-outlined text-secondary">verified_user</span><span class="font-mono text-xs font-bold tracking-widest">PCI COMPLIANT</span></div>
                </div>
            @endif
        </div>
        @if($cart->items->isNotEmpty())
            <aside class="sticky top-24 lg:col-span-4">
                <div class="rounded-xl border border-outline-variant bg-surface-container-high p-6 shadow-sm">
                    <h2 class="mb-6 font-display text-lg font-semibold">Order Summary</h2>
                    <div class="mb-6 border-b border-outline-variant pb-6">
                        @if($cart->coupon)
                            <div class="mb-4 flex items-center justify-between rounded-lg border border-secondary-fixed-dim/50 bg-secondary-container/15 px-3 py-2.5 text-sm">
                                <span class="flex items-center gap-1.5 font-mono font-bold text-on-secondary-container"><span class="material-symbols-outlined text-[18px]" aria-hidden="true">sell</span>{{ $cart->coupon->code }}</span>
                                <form method="POST" action="{{ route('cart.coupon.remove') }}">@csrf @method('DELETE')<button class="font-semibold text-on-surface-variant hover:text-error">Remove</button></form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('cart.coupon.apply') }}" class="mb-4 flex gap-2">
                                @csrf
                                <input name="code" value="{{ old('code') }}" placeholder="Coupon code" aria-label="Coupon code" class="min-w-0 flex-1 rounded-lg border border-outline-variant bg-surface px-3 py-2 font-mono text-sm uppercase placeholder:font-sans placeholder:normal-case focus:outline-none focus:ring-2 focus:ring-primary/20">
                                <button class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition-colors hover:bg-primary-container/10">Apply</button>
                            </form>
                        @endif
                        @error('coupon')<p class="mb-4 text-sm text-error">{{ $message }}</p>@enderror
                        @error('code')<p class="mb-4 text-sm text-error">{{ $message }}</p>@enderror
                        <div class="space-y-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">Subtotal</span>
                            <span class="font-mono">${{ number_format($totals['subtotal'], 2) }}</span>
                        </div>
                        @if($totals['discount'] > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">Discount{{ $cart->coupon ? ' ('.$cart->coupon->code.')' : '' }}</span>
                            <span class="font-mono text-secondary">−${{ number_format($totals['discount'], 2) }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">Tax</span>
                            <span class="font-mono">${{ number_format($totals['tax'], 2) }}</span>
                        </div>
                        </div>
                    </div>
                    <div class="mb-8 flex items-end justify-between">
                        <span class="font-semibold">Total Due</span>
                        <span class="font-display text-4xl font-bold leading-none text-primary">${{ number_format($totals['total'], 2) }}</span>
                    </div>
                    <form method="POST" action="{{ route('checkout.store') }}">
                        @csrf
                        <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-4 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">
                            <span>Proceed to Payment</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                    <div class="mt-8 space-y-4">
                        <div class="flex items-center gap-4 rounded-lg border border-outline-variant/30 bg-surface/50 p-4">
                            <span class="material-symbols-outlined text-primary">download_for_offline</span>
                            <div class="text-sm leading-tight">
                                <p class="font-bold">Instant Delivery</p>
                                <p class="text-xs text-on-surface-variant">Files available immediately after payment.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border border-outline-variant/30 bg-surface/50 p-4">
                            <span class="material-symbols-outlined text-primary">verified</span>
                            <div class="text-sm leading-tight">
                                <p class="font-bold">Authentic License</p>
                                <p class="text-xs text-on-surface-variant">Every asset is reviewed before publication.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mt-6 px-6 text-center text-xs text-on-surface-variant">
                    By clicking "Proceed to Payment", you agree to our
                    <a class="underline hover:text-primary" href="{{ route('pages.show', 'terms-of-service') }}">Terms of Service</a> and
                    <a class="underline hover:text-primary" href="{{ route('pages.show', 'seller-agreement') }}">Seller Agreement</a>.
                </p>
            </aside>
        @endif
    </div>
</div>
</x-marketplace-layout>
