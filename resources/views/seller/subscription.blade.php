<x-marketplace-layout title="Plan — Seller Studio">
<div class="mx-auto max-w-7xl px-6 py-10">
    <div class="mb-6">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Subscription plan</h1>
    </div>
    <x-seller-nav />

    @if(session('status'))<div class="mb-6 flex items-center gap-2 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container"><span class="material-symbols-outlined text-[20px]">check_circle</span>{{ session('status') }}</div>@endif

    @php($periodLabel = ['weekly' => '/week', 'monthly' => '/month', 'yearly' => '/year', 'lifetime' => ' one-time'])

    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Current plan</p>
            <p class="mt-1 font-display text-xl font-bold">{{ $current?->plan->name ?? 'Starter (free)' }}</p>
            @if($current && $current->ends_at)<p class="text-xs text-on-surface-variant">Renews/ends {{ $current->ends_at->toFormattedDateString() }}</p>@endif
        </div>
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Commission rate</p>
            <p class="mt-1 font-display text-xl font-bold">{{ $current?->commission_rate !== null ? number_format((float) $current->commission_rate, 0).'%' : 'Standard' }}</p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Active listings</p>
            <p class="mt-1 font-display text-xl font-bold">{{ $listingCount }}{{ $current?->listing_limit ? ' / '.$current->listing_limit : '' }}</p>
        </div>
    </div>

    @if($pending)
        <div class="mb-8 flex items-center gap-3 rounded-xl border border-tertiary-fixed-dim/60 bg-tertiary-fixed/10 px-4 py-3 text-sm">
            <span class="material-symbols-outlined text-[20px]">hourglass_top</span>
            Your <strong>{{ $pending->plan->name }}</strong> subscription is pending activation. It goes live once an administrator confirms your payment.
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach($plans as $plan)
            @php($isCurrent = $current && $current->subscription_plan_id === $plan->id)
            <div class="flex flex-col rounded-2xl border p-6 {{ $isCurrent ? 'border-primary bg-primary/5' : 'border-outline-variant bg-surface-container-lowest' }}">
                <h2 class="font-display text-lg font-bold">{{ $plan->name }}</h2>
                <p class="mt-2"><span class="font-display text-3xl font-bold">${{ number_format((float) $plan->price, 0) }}</span><span class="text-sm text-on-surface-variant">{{ $periodLabel[$plan->billing_period] ?? '' }}</span></p>
                <p class="mt-1 text-sm font-semibold text-primary">{{ $plan->commission_rate !== null ? number_format((float) $plan->commission_rate, 0).'% commission' : 'Standard commission' }}</p>
                <ul class="mt-4 flex-1 space-y-2 text-sm text-on-surface-variant">
                    @foreach(($plan->features ?? []) as $feature)
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-[18px] text-secondary">check</span>{{ $feature }}</li>
                    @endforeach
                </ul>
                <div class="mt-6">
                    @if($isCurrent)
                        <span class="block rounded-xl bg-primary/10 py-2.5 text-center text-sm font-semibold text-primary">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('seller.subscription.subscribe', $plan) }}">@csrf
                            <button class="w-full rounded-xl bg-primary py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">{{ $plan->isFree() ? 'Switch to '.$plan->name : 'Subscribe' }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($current && (float) $current->price > 0)
        <form method="POST" action="{{ route('seller.subscription.cancel', $current) }}" class="mt-8" onsubmit="return confirm('Cancel your current subscription?')">@csrf
            <button class="text-sm font-semibold text-on-surface-variant hover:text-error">Cancel current subscription</button>
        </form>
    @endif
</div>
</x-marketplace-layout>
