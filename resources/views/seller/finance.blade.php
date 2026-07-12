<x-marketplace-layout title="Earnings & Payouts — DiginMarket">
<div class="mx-auto max-w-7xl px-6 py-12">
    <div class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end">
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Wallet</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Earnings & Payouts</h1>
        </div>
        @if(!$connected?->payouts_enabled)
            <a href="{{ route('seller.connect.start') }}" class="flex h-fit items-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">account_balance</span>
                Connect Stripe
            </a>
        @else
            <span class="flex h-fit items-center gap-2 rounded-xl bg-secondary-container/40 px-5 py-3 font-semibold text-on-secondary-container">
                <span class="material-symbols-outlined icon-fill text-[20px]">check_circle</span>
                Stripe payouts enabled
            </span>
        @endif
    </div>
    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
        @foreach(['pending_balance' => ['Pending', 'hourglass_top'], 'available_balance' => ['Available', 'account_balance_wallet'], 'reserved_balance' => ['Reserved', 'lock'], 'withdrawn_balance' => ['Withdrawn', 'north_east'], 'lifetime_earnings' => ['Lifetime', 'trending_up']] as $field => [$label, $icon])
            <div class="rounded-xl border border-outline-variant bg-surface-container p-5">
                <header class="flex items-center justify-between">
                    <span class="text-sm font-medium text-on-surface-variant">{{ $label }}</span>
                    <span class="material-symbols-outlined text-[20px] {{ $field === 'available_balance' ? 'text-secondary' : 'text-primary' }}">{{ $icon }}</span>
                </header>
                <p class="mt-3 font-display text-2xl font-bold {{ $field === 'available_balance' ? 'text-secondary' : '' }}">${{ number_format((float) $wallet->$field, 2) }}</p>
            </div>
        @endforeach
    </div>
    <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_360px]">
        <section>
            <h2 class="mb-5 font-display text-2xl font-semibold tracking-tight">Ledger</h2>
            <div class="overflow-x-auto rounded-xl border border-outline-variant bg-surface-container-lowest">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-outline-variant bg-surface-container-low">
                        <tr class="font-mono text-[11px] uppercase tracking-wider text-on-surface-variant">
                            <th class="p-4 font-medium">Type</th>
                            <th class="p-4 font-medium">Bucket</th>
                            <th class="p-4 font-medium">Amount</th>
                            <th class="p-4 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-t border-outline-variant/50">
                                <td class="p-4 font-medium">{{ str($transaction->type)->headline() }}</td>
                                <td class="p-4 text-on-surface-variant">{{ str($transaction->balance_bucket)->headline() }}</td>
                                <td class="p-4 font-mono font-medium {{ $transaction->amount >= 0 ? 'text-secondary' : 'text-on-surface-variant' }}">{{ $transaction->amount >= 0 ? '+' : '' }}${{ number_format(abs((float) $transaction->amount), 2) }}</td>
                                <td class="p-4 font-mono text-xs uppercase text-on-surface-variant">{{ $transaction->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-10 text-center text-on-surface-variant">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </section>
        <aside>
            <div class="rounded-xl border border-outline-variant bg-surface-container-high p-6">
                <h2 class="font-display text-xl font-semibold">Request Withdrawal</h2>
                <p class="mt-2 text-sm text-on-surface-variant">Minimum ${{ config('marketplace.minimum_withdrawal') }}. Funds are reserved during review.</p>
                <form method="POST" action="{{ route('seller.withdrawals.store') }}" class="mt-5">
                    @csrf
                    <input name="amount" type="number" step="0.01" max="{{ $wallet->available_balance }}" placeholder="Amount"
                        class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <button class="mt-3 w-full rounded-xl bg-primary p-3 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Request payout</button>
                </form>
                @error('amount')<p class="mt-3 text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <h3 class="mt-8 font-display font-semibold">Recent Withdrawals</h3>
            <div class="mt-3 space-y-3">
                @forelse($withdrawals as $withdrawal)
                    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-4">
                        <div class="flex justify-between">
                            <span class="font-mono text-xs uppercase tracking-wider text-on-surface-variant">{{ $withdrawal->number }}</span>
                            <strong class="font-display">${{ number_format((float) $withdrawal->amount, 2) }}</strong>
                        </div>
                        <span class="mt-2 inline-block rounded px-2 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider {{ in_array($withdrawal->status, ['paid', 'approved'], true) ? 'bg-secondary-container/40 text-on-secondary-container' : ($withdrawal->status === 'rejected' ? 'bg-error-container text-on-error-container' : 'bg-primary-container/20 text-primary') }}">{{ str($withdrawal->status)->headline() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-on-surface-variant">No withdrawals.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
</x-marketplace-layout>
