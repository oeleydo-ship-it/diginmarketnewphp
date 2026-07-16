{{-- Full withdrawal form with method picker. Pre-filled from the saved default ($dm/$dd) when present. --}}
<form method="POST" action="{{ route('seller.withdrawals.store') }}" class="mt-4 space-y-3" data-payout-form>
    @csrf
    <input name="amount" type="number" step="0.01" max="{{ $wallet->available_balance }}" value="{{ old('amount') }}" placeholder="Amount" class="{{ $fld }}">
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Payout method</label>
        <select name="payout_method" data-payout-method class="{{ $fld }}">
            <option value="stripe" @selected(old('payout_method', $dm ?? 'stripe') === 'stripe')>Stripe Connect</option>
            <option value="paypal" @selected(old('payout_method', $dm) === 'paypal')>PayPal</option>
            <option value="bank" @selected(old('payout_method', $dm) === 'bank')>Bank transfer</option>
        </select>
    </div>
    <div data-payout-fields="stripe" class="rounded-lg bg-surface-container-lowest p-3 text-xs text-on-surface-variant">
        Paid to your connected Stripe account.
    </div>
    <div data-payout-fields="paypal" class="hidden">
        <input name="paypal_email" type="email" value="{{ old('paypal_email', $dd['email'] ?? '') }}" placeholder="PayPal email" class="{{ $fld }}">
    </div>
    <div data-payout-fields="bank" class="hidden space-y-2">
        <input name="bank_name" value="{{ old('bank_name', $dd['bank_name'] ?? '') }}" placeholder="Bank name" class="{{ $fld }}">
        <input name="account_name" value="{{ old('account_name', $dd['account_name'] ?? '') }}" placeholder="Account holder name" class="{{ $fld }}">
        <input name="account_number" value="{{ old('account_number', $dd['account_number'] ?? '') }}" placeholder="Account number / IBAN" class="{{ $fld }}">
        <div class="flex gap-2">
            <input name="routing_number" value="{{ old('routing_number', $dd['routing_number'] ?? '') }}" placeholder="Routing (optional)" class="{{ $fld }}">
            <input name="swift" value="{{ old('swift', $dd['swift'] ?? '') }}" placeholder="SWIFT/BIC (optional)" class="{{ $fld }}">
        </div>
    </div>
    @if($showSaveDefault ?? false)
        <label class="flex items-center gap-2 text-sm text-on-surface-variant">
            <input type="checkbox" name="save_default" value="1" class="rounded border-outline-variant text-primary focus:ring-primary/20">
            Save this as my default payout method
        </label>
    @endif
    <button class="w-full rounded-xl bg-primary p-3 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Request payout</button>
</form>
