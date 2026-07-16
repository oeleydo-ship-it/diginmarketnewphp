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
    <x-seller-nav />
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
            @php
                $fld = 'w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20';
                $labels = ['stripe' => 'Stripe Connect', 'paypal' => 'PayPal', 'bank' => 'Bank transfer'];
                $dm = $profile?->default_payout_method;
                $dd = $profile?->default_payout_details ?? [];
                $defaultDest = $dm === 'paypal' ? ($dd['email'] ?? '') : ($dm === 'bank' ? trim(($dd['bank_name'] ?? '').' ····'.substr($dd['account_number'] ?? '', -4)) : 'Connected Stripe account');
            @endphp
            @php($minWithdrawal = (float) config('marketplace.minimum_withdrawal'))
            @php($canWithdraw = (float) $wallet->available_balance >= $minWithdrawal)
            <div class="rounded-xl border border-outline-variant bg-surface-container-high p-6">
                <h2 class="font-display text-xl font-semibold">Request Withdrawal</h2>
                <p class="mt-2 text-sm text-on-surface-variant">Minimum ${{ config('marketplace.minimum_withdrawal') }}. Funds are reserved during review.</p>

                @if(! $canWithdraw)
                    {{-- Nothing withdrawable yet: explain the clearance window instead of rendering a form that can only fail. --}}
                    <div class="mt-5 rounded-lg border border-tertiary-fixed-dim/50 bg-tertiary-fixed/10 p-4 text-sm">
                        <p class="flex items-center gap-2 font-semibold"><span class="material-symbols-outlined text-[18px]">hourglass_top</span>No funds available to withdraw yet</p>
                        @if((float) $wallet->pending_balance > 0)
                            <p class="mt-2 text-on-surface-variant">
                                ${{ number_format((float) $wallet->pending_balance, 2) }} is pending clearance — new earnings are held for {{ (int) config('marketplace.earnings_clearance_days') }} days to cover the refund window.
                                @if($nextClearance)Your earliest earning becomes available on <strong>{{ $nextClearance->toFormattedDateString() }}</strong>.@endif
                            </p>
                        @else
                            <p class="mt-2 text-on-surface-variant">Your available balance is below the ${{ number_format($minWithdrawal, 2) }} minimum. Keep selling — earnings appear here once they clear.</p>
                        @endif
                    </div>
                @elseif($profile?->hasDefaultPayout())
                    <div class="mt-5 rounded-lg border border-primary/30 bg-primary/5 p-4">
                        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-primary"><span class="material-symbols-outlined text-[16px]">bookmark</span>Saved payout method</p>
                        <p class="mt-1 font-semibold">{{ $labels[$dm] ?? 'Stripe Connect' }}</p>
                        <p class="font-mono text-xs text-on-surface-variant">{{ $defaultDest }}</p>
                        <form method="POST" action="{{ route('seller.withdrawals.store') }}" class="mt-3 flex gap-2">
                            @csrf
                            <input type="hidden" name="use_default" value="1">
                            <input name="amount" type="number" step="0.01" required min="{{ $minWithdrawal }}" max="{{ $wallet->available_balance }}" placeholder="Amount" class="{{ $fld }}">
                            <button class="whitespace-nowrap rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Withdraw</button>
                        </form>
                    </div>
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-semibold text-primary hover:underline">Use a different method once</summary>
                        @include('seller.partials.withdrawal-form', ['fld' => $fld, 'wallet' => $wallet, 'dm' => $dm, 'dd' => $dd, 'showSaveDefault' => true])
                    </details>
                @else
                    @include('seller.partials.withdrawal-form', ['fld' => $fld, 'wallet' => $wallet, 'dm' => null, 'dd' => [], 'showSaveDefault' => true])
                @endif
                @foreach($errors->all() as $err)<p class="mt-3 text-sm text-error">{{ $err }}</p>@endforeach
            </div>

            <!-- Payout settings: save a default so you only enter an amount next time -->
            <details class="mt-6 rounded-xl border border-outline-variant bg-surface-container-lowest" @if(!$profile?->hasDefaultPayout()) open @endif>
                <summary class="flex cursor-pointer items-center justify-between p-5 font-display font-semibold">
                    <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-primary">settings</span>Payout settings</span>
                    @if($profile?->hasDefaultPayout())<span class="rounded-full bg-secondary-container/40 px-2.5 py-0.5 text-xs font-semibold text-on-secondary-container">{{ $labels[$dm] ?? 'Stripe' }}</span>@endif
                </summary>
                <div class="border-t border-outline-variant p-5">
                    <p class="mb-4 text-sm text-on-surface-variant">Save your default payout method once — future withdrawals only need an amount.</p>
                    <form method="POST" action="{{ route('seller.payout-settings.update') }}" class="space-y-3" data-payout-form>
                        @csrf @method('PUT')
                        <select name="payout_method" data-payout-method class="{{ $fld }}">
                            <option value="stripe" @selected(($dm ?? 'stripe') === 'stripe')>Stripe Connect</option>
                            <option value="paypal" @selected($dm === 'paypal')>PayPal</option>
                            <option value="bank" @selected($dm === 'bank')>Bank transfer</option>
                        </select>
                        <div data-payout-fields="stripe" class="rounded-lg bg-surface-container-high p-3 text-xs text-on-surface-variant">Paid to your connected Stripe account.</div>
                        <div data-payout-fields="paypal" class="hidden">
                            <input name="paypal_email" type="email" value="{{ $dd['email'] ?? '' }}" placeholder="PayPal email" class="{{ $fld }}">
                        </div>
                        <div data-payout-fields="bank" class="hidden space-y-2">
                            <input name="bank_name" value="{{ $dd['bank_name'] ?? '' }}" placeholder="Bank name" class="{{ $fld }}">
                            <input name="account_name" value="{{ $dd['account_name'] ?? '' }}" placeholder="Account holder name" class="{{ $fld }}">
                            <input name="account_number" value="{{ $dd['account_number'] ?? '' }}" placeholder="Account number / IBAN" class="{{ $fld }}">
                            <div class="flex gap-2">
                                <input name="routing_number" value="{{ $dd['routing_number'] ?? '' }}" placeholder="Routing (optional)" class="{{ $fld }}">
                                <input name="swift" value="{{ $dd['swift'] ?? '' }}" placeholder="SWIFT/BIC (optional)" class="{{ $fld }}">
                            </div>
                        </div>
                        <button class="w-full rounded-lg border border-primary py-2.5 text-sm font-semibold text-primary transition-all hover:bg-primary hover:text-on-primary active:scale-95">Save default</button>
                    </form>
                </div>
            </details>

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
