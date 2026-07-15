<x-marketplace-layout title="My Coupons — DiginMarket">
<div class="mx-auto max-w-7xl px-6 py-12">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">My Coupons</h1>
            <p class="mt-2 text-on-surface-variant">Discount codes that apply only to your products, even in mixed carts.</p>
        </div>
    </div>
    <x-seller-nav />
    @if(session('status'))<div class="mb-6 rounded-xl border border-outline-variant bg-secondary-container/40 px-4 py-3 text-sm text-on-secondary-container">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-6 rounded-xl border border-error bg-error-container px-4 py-3 text-sm text-on-error-container">{{ $errors->first() }}</div>@endif
    @php($input='rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm outline-none focus:border-primary')
    @php($label='text-xs font-semibold uppercase tracking-wide text-on-surface-variant')
    <div class="rounded-xl border border-outline-variant bg-surface-container-low p-6">
        <h2 class="flex items-center gap-2 font-display text-lg font-bold"><span class="material-symbols-outlined text-primary">add_circle</span>Create coupon</h2>
        <form method="POST" action="{{ route('seller.coupons.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Code</label><input name="code" required placeholder="MYSTORE20" value="{{ old('code') }}" class="{{ $input }} font-mono uppercase"></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Type</label><select name="type" class="{{ $input }}"><option value="percent">Percent off</option><option value="fixed" @selected(old('type')==='fixed')>Fixed amount off</option></select></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Value</label><input name="value" required type="number" step="0.01" min="0.01" placeholder="20" value="{{ old('value') }}" class="{{ $input }}"></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Minimum eligible total</label><input name="min_cart_total" type="number" step="0.01" min="0" placeholder="No minimum" value="{{ old('min_cart_total') }}" class="{{ $input }}"></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Max total uses</label><input name="max_uses" type="number" min="1" placeholder="Unlimited" value="{{ old('max_uses') }}" class="{{ $input }}"></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Max uses per customer</label><input name="max_uses_per_user" type="number" min="1" value="{{ old('max_uses_per_user',1) }}" class="{{ $input }}"></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Starts</label><input name="starts_at" type="date" value="{{ old('starts_at') }}" class="{{ $input }}"></div>
            <div class="flex flex-col gap-1"><label class="{{ $label }}">Ends</label><input name="ends_at" type="date" value="{{ old('ends_at') }}" class="{{ $input }}"></div>
            <div class="flex flex-col gap-1 sm:col-span-2 lg:col-span-3"><label class="{{ $label }}">Description</label><input name="description" placeholder="Internal note" value="{{ old('description') }}" class="{{ $input }}"></div>
            <button class="self-end rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Create coupon</button>
        </form>
    </div>
    <div class="mt-8 overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
        <div class="overflow-x-auto"><table class="w-full min-w-[860px] text-left text-sm">
            <thead class="bg-surface-container text-xs uppercase tracking-[.08em] text-on-surface-variant"><tr><th class="px-5 py-4">Code</th><th class="px-5 py-4">Discount</th><th class="px-5 py-4">Rules</th><th class="px-5 py-4">Usage</th><th class="px-5 py-4">Window</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-outline-variant">
            @forelse($coupons as $coupon)
                <tr>
                    <td class="px-5 py-4"><span class="font-mono font-bold text-primary">{{ $coupon->code }}</span>@if($coupon->description)<p class="mt-0.5 text-xs text-on-surface-variant">{{ $coupon->description }}</p>@endif</td>
                    <td class="px-5 py-4 font-semibold">{{ $coupon->type==='percent' ? rtrim(rtrim(number_format($coupon->value,2),'0'),'.').'%' : '$'.number_format($coupon->value,2) }}</td>
                    <td class="px-5 py-4 text-on-surface-variant">@if($coupon->min_cart_total)Min ${{ number_format($coupon->min_cart_total,2) }} · @endif{{ $coupon->max_uses_per_user }}/customer</td>
                    <td class="px-5 py-4">{{ number_format($coupon->used_count) }}{{ $coupon->max_uses ? ' / '.number_format($coupon->max_uses) : '' }}</td>
                    <td class="px-5 py-4 text-on-surface-variant">{{ $coupon->starts_at?->format('M j') ?? '—' }} → {{ $coupon->ends_at?->format('M j, Y') ?? 'no end' }}</td>
                    <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $coupon->is_active ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-outline-variant/30 text-on-surface-variant' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-5 py-4">
                        <div class="flex justify-end gap-2">
                            <form method="POST" action="{{ route('seller.coupons.toggle',$coupon) }}">@csrf @method('PUT')<button class="rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold transition-all hover:border-primary hover:text-primary">{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            @if(!$coupon->usages_count)<form method="POST" action="{{ route('seller.coupons.destroy',$coupon) }}" onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">@csrf @method('DELETE')<button class="rounded-lg border border-error px-3 py-1.5 text-xs font-semibold text-error transition-all hover:bg-error-container">Delete</button></form>@endif
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="7" class="px-6 py-16 text-center text-on-surface-variant"><span class="material-symbols-outlined mb-2 block text-4xl">sell</span>No coupons yet. Create a code above to run a promotion on your catalog.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-6">{{ $coupons->links() }}</div>
</div>
</x-marketplace-layout>
