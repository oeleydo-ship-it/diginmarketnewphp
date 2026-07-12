<x-marketplace-layout title="Affiliate Program — DiginMarket">
<x-customer-panel>
    <header class="mb-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">Affiliate Program</h1>
        <p class="mt-1 text-on-surface-variant">Earn {{ rtrim(rtrim(number_format((float) config('marketplace.affiliate_commission_rate', 20), 2), '0'), '.') }}% of the platform commission on every sale you refer.</p>
    </header>
    @if(!$profile)
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-10 text-center">
            <span class="material-symbols-outlined mb-4 text-[48px] text-primary">share</span>
            <h2 class="font-display text-xl font-semibold">Start earning with referrals</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-on-surface-variant">Share your unique link. When someone buys within 30 days, you earn a commission — credited automatically once the payment clears.</p>
            <form method="POST" action="{{ route('affiliates.store') }}" class="mt-6">
                @csrf
                <button class="rounded-xl bg-primary px-6 py-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Join the program</button>
            </form>
        </div>
    @else
        <div class="mb-8 rounded-xl border border-outline-variant bg-surface-container-high p-6">
            <p class="mb-2 font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Your referral link</p>
            <div class="flex flex-wrap items-center gap-3">
                <code id="aff-link" class="min-w-0 flex-1 truncate rounded-lg bg-surface px-4 py-3 font-mono text-sm">{{ route('home', ['ref' => $profile->code]) }}</code>
                <button onclick="navigator.clipboard.writeText(document.getElementById('aff-link').textContent).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.innerHTML = '<span class=&quot;material-symbols-outlined text-[18px]&quot;>content_copy</span> Copy', 1500); })"
                    class="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">content_copy</span> Copy
                </button>
            </div>
            <p class="mt-3 text-xs text-on-surface-variant">Append <code class="rounded bg-surface px-1.5 py-0.5 font-mono">?ref={{ $profile->code }}</code> to any product or category URL — attribution lasts 30 days.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-outline-variant bg-surface-container p-5">
                <header class="flex items-center justify-between"><span class="text-sm font-medium text-on-surface-variant">Link clicks</span><span class="material-symbols-outlined text-[20px] text-primary">ads_click</span></header>
                <p class="mt-3 font-display text-2xl font-bold">{{ number_format($profile->clicks) }}</p>
            </div>
            <div class="rounded-xl border border-outline-variant bg-surface-container p-5">
                <header class="flex items-center justify-between"><span class="text-sm font-medium text-on-surface-variant">Referred orders</span><span class="material-symbols-outlined text-[20px] text-primary">shopping_bag</span></header>
                <p class="mt-3 font-display text-2xl font-bold">{{ number_format($profile->referred_orders) }}</p>
            </div>
            <div class="rounded-xl border border-outline-variant bg-surface-container p-5">
                <header class="flex items-center justify-between"><span class="text-sm font-medium text-on-surface-variant">Total earnings</span><span class="material-symbols-outlined text-[20px] text-secondary">payments</span></header>
                <p class="mt-3 font-display text-2xl font-bold text-secondary">${{ number_format((float) $profile->total_earnings, 2) }}</p>
            </div>
        </div>
        <section class="mt-8">
            <h2 class="mb-4 font-display text-xl font-semibold">Earnings</h2>
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                <div class="divide-y divide-outline-variant/50">
                    @forelse($earnings as $earning)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <span class="font-mono text-sm font-medium text-primary">{{ $earning->order->number }}</span>
                                <p class="mt-0.5 font-mono text-xs uppercase text-on-surface-variant">{{ $earning->created_at->format('M j, Y') }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="rounded px-2 py-1 font-mono text-[10px] font-bold uppercase tracking-wider {{ $earning->status === 'paid' ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-primary-container/20 text-primary' }}">{{ $earning->status }}</span>
                                <span class="font-mono font-medium text-secondary">+${{ number_format((float) $earning->amount, 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-on-surface-variant">No referral earnings yet. Share your link to get started.</div>
                    @endforelse
                </div>
            </div>
            <div class="mt-4">{{ $earnings->links() }}</div>
        </section>
    @endif
</x-customer-panel>
</x-marketplace-layout>
