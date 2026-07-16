<x-marketplace-layout title="Bank Transfer Instructions — DiginMarket">
<div class="relative overflow-hidden px-6 py-20">
    <div class="pointer-events-none absolute left-1/4 top-1/4 h-96 w-96 rounded-full bg-primary/5 blur-[100px]"></div>
    <div class="relative z-20 mx-auto w-full max-w-2xl">
        <div class="mb-10 text-center">
            <div class="mb-6 inline-flex h-24 w-24 items-center justify-center rounded-full bg-tertiary-fixed text-on-tertiary-fixed-variant">
                <span class="material-symbols-outlined !text-[48px]">account_balance</span>
            </div>
            <h1 class="mb-2 font-display text-4xl font-bold tracking-tight text-primary md:text-5xl">{{ __('Complete Your Bank Transfer') }}</h1>
            <p class="mx-auto max-w-md text-on-surface-variant">
                {{ __('Order') }}
                <span class="rounded bg-surface-container px-2 py-0.5 font-mono text-xs font-medium">{{ $order->number }}</span>
                {{ __('is reserved. Your downloads and licenses unlock once we confirm the funds have arrived.') }}
            </p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest/70 p-6 backdrop-blur-md">
            <div class="mb-6 flex items-center justify-between border-b border-outline-variant pb-4">
                <span class="text-sm text-on-surface-variant">{{ __('Amount to transfer') }}</span>
                <span class="font-display text-2xl font-bold">{{ number_format($order->total, 2) }} <span class="text-sm font-medium text-on-surface-variant">{{ $order->currency }}</span></span>
            </div>
            <h2 class="mb-3 font-mono text-xs font-medium uppercase tracking-wider text-on-surface-variant">{{ __('Transfer Instructions') }}</h2>
            <div class="whitespace-pre-line text-sm leading-relaxed text-on-surface">{{ $instructions ?: __('Transfer instructions have not been configured yet. Please contact support to complete this payment.') }}</div>
            <p class="mt-6 rounded-lg bg-surface-container/60 p-4 text-xs text-on-surface-variant">
                <span class="font-semibold">{{ __('Reference:') }}</span> {{ $order->number }} — {{ __('include this so we can match your payment quickly.') }}
            </p>
        </div>
        <div class="mt-10 text-center">
            <a href="{{ route('purchases.show', $order) }}" class="group inline-flex items-center gap-2 font-semibold text-primary hover:underline">
                {{ __('View Order Status') }}
                <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
            </a>
        </div>
    </div>
</div>
</x-marketplace-layout>
