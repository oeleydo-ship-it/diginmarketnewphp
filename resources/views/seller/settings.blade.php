<x-marketplace-layout title="Settings — Seller Studio">
<div class="mx-auto max-w-7xl px-6 py-10">
    <div class="mb-6">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Settings</h1>
    </div>
    <x-seller-nav />

    @if(session('status'))<div class="mb-6 flex items-center gap-2 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container"><span class="material-symbols-outlined text-[20px]" aria-hidden="true">check_circle</span>{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-6 rounded-xl border border-error/30 bg-error-container/40 px-4 py-3 text-sm font-medium text-on-error-container">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    @php($input='w-full rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20')
    @php($label='mb-1 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant')

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <form method="POST" action="{{ route('seller.settings.update') }}" class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 lg:col-span-7">
            @csrf @method('PUT')
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold"><span class="material-symbols-outlined text-primary" aria-hidden="true">storefront</span>Storefront profile</h2>
            <p class="mt-1 text-sm text-on-surface-variant">Shown on your public author page and product listings.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><label for="display_name" class="{{ $label }}">Seller name</label><input id="display_name" name="display_name" required value="{{ old('display_name',$profile->display_name) }}" class="{{ $input }}"></div>
                <div><label for="business_name" class="{{ $label }}">Company / business name</label><input id="business_name" name="business_name" value="{{ old('business_name',$profile->business_name) }}" class="{{ $input }}"></div>
                <div><label for="website" class="{{ $label }}">Website</label><input id="website" name="website" type="url" placeholder="https://" value="{{ old('website',$profile->website) }}" class="{{ $input }}"></div>
                <div><label for="phone" class="{{ $label }}">Phone</label><input id="phone" name="phone" value="{{ old('phone',$profile->phone) }}" class="{{ $input }}"></div>
                <div class="sm:col-span-2"><label for="biography" class="{{ $label }}">Biography</label><textarea id="biography" name="biography" rows="4" required class="{{ $input }}">{{ old('biography',$profile->biography) }}</textarea></div>
                <div class="sm:col-span-2"><label for="address" class="{{ $label }}">Address</label><input id="address" name="address" value="{{ old('address',$profile->address) }}" class="{{ $input }}"></div>
                <div><label for="city" class="{{ $label }}">City</label><input id="city" name="city" value="{{ old('city',$profile->city) }}" class="{{ $input }}"></div>
                <div><label for="postal_code" class="{{ $label }}">Postal code</label><input id="postal_code" name="postal_code" value="{{ old('postal_code',$profile->postal_code) }}" class="{{ $input }}"></div>
            </div>
            <button class="mt-6 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-on-primary shadow-md transition-all hover:opacity-90 active:scale-95">Save settings</button>
        </form>

        <div class="space-y-6 lg:col-span-5">
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
                <h2 class="flex items-center gap-2 font-display text-lg font-semibold"><span class="material-symbols-outlined text-primary" aria-hidden="true">badge</span>Submitted application data</h2>
                <p class="mt-1 text-sm text-on-surface-variant">Verified identity details from your seller application. Contact support to change them.</p>
                <dl class="mt-5 space-y-3 text-sm">
                    @foreach([
                        ['Full name',$profile->full_name],
                        ['Username','@'.$profile->username],
                        ['Country',$profile->country],
                        ['Account email',$profile->user->email],
                        ['Applied',$profile->created_at->format('M j, Y')],
                        ['Reviewed',$profile->reviewed_at?->format('M j, Y') ?? 'Pending'],
                    ] as [$term,$value])
                    <div class="flex items-center justify-between gap-4 border-b border-outline-variant/50 pb-2"><dt class="text-on-surface-variant">{{ $term }}</dt><dd class="font-medium">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                    <div class="flex items-center justify-between gap-4"><dt class="text-on-surface-variant">Status</dt><dd><span class="rounded-full bg-secondary-container/40 px-3 py-1 text-xs font-bold uppercase text-on-secondary-container">{{ $profile->status->value }}</span></dd></div>
                </dl>
            </div>
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
                <h2 class="flex items-center gap-2 font-display text-lg font-semibold"><span class="material-symbols-outlined text-primary" aria-hidden="true">account_balance_wallet</span>Payouts</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4"><dt class="text-on-surface-variant">Default payout method</dt><dd class="font-medium capitalize">{{ $profile->default_payout_method ? str_replace('_',' ',$profile->default_payout_method) : 'Not set' }}</dd></div>
                </dl>
                <a href="{{ route('seller.finance') }}" class="mt-4 inline-block text-sm font-semibold text-primary hover:underline">Manage payout methods →</a>
            </div>
        </div>
    </div>
</div>
</x-marketplace-layout>
