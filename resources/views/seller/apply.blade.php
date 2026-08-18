<x-marketplace-layout title="Become a Seller — DiginMarket">
<div class="mx-auto max-w-2xl px-6 py-16">
    <header class="mb-10">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Onboarding</p>
        <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">Open your storefront</h1>
        <p class="mt-3 text-on-surface-variant">Tell us who you are. Your legal details are used for verification only — buyers will see your vendor name.</p>
    </header>
    @php($input = 'w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20')
    @php($labelCls = 'font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant')
    <form method="POST" action="{{ route('seller.apply.store') }}" class="space-y-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        @csrf
        <section>
            <h2 class="mb-1 flex items-center gap-2 font-display text-lg font-semibold"><span class="material-symbols-outlined text-primary">badge</span>Identity Verification</h2>
            <p class="mb-4 text-sm text-on-surface-variant">Must match your government ID. Reviewed by our team before approval.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="full_name" class="{{ $labelCls }}">Full legal name *</label>
                    <input id="full_name" name="full_name" required value="{{ old('full_name') }}" placeholder="e.g. Johnathan A. Doe" class="{{ $input }}">
                    @error('full_name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="address" class="{{ $labelCls }}">Street address *</label>
                    <input id="address" name="address" required value="{{ old('address') }}" placeholder="123 Tech Avenue, Suite 4" class="{{ $input }}">
                    @error('address')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="city" class="{{ $labelCls }}">City *</label>
                    <input id="city" name="city" required value="{{ old('city') }}" class="{{ $input }}">
                    @error('city')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="postal_code" class="{{ $labelCls }}">Postal code</label>
                    <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" class="{{ $input }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="country" class="{{ $labelCls }}">Country code *</label>
                    <input id="country" name="country" required maxlength="2" value="{{ old('country') }}" placeholder="e.g. AE" class="{{ $input }} font-mono uppercase">
                    @error('country')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="phone" class="{{ $labelCls }}">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" class="{{ $input }}">
                </div>
            </div>
        </section>
        <section class="border-t border-outline-variant pt-8">
            <h2 class="mb-1 flex items-center gap-2 font-display text-lg font-semibold"><span class="material-symbols-outlined text-primary">storefront</span>Storefront</h2>
            <p class="mb-4 text-sm text-on-surface-variant">What buyers will see on your public vendor page.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="display_name" class="{{ $labelCls }}">Vendor / display name *</label>
                    <input id="display_name" name="display_name" required value="{{ old('display_name') }}" placeholder="Shown on your storefront" class="{{ $input }}">
                    @error('display_name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="username" class="{{ $labelCls }}">Seller username *</label>
                    <input id="username" name="username" required value="{{ old('username') }}" placeholder="Used in your storefront URL" class="{{ $input }} font-mono">
                    @error('username')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="business_name" class="{{ $labelCls }}">Business name</label>
                    <input id="business_name" name="business_name" value="{{ old('business_name') }}" placeholder="Optional — registered company or trading name" class="{{ $input }}">
                    @error('business_name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="website" class="{{ $labelCls }}">Website</label>
                    <input id="website" name="website" value="{{ old('website') }}" placeholder="https://" class="{{ $input }}">
                    @error('website')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="biography" class="{{ $labelCls }}">Biography *</label>
                    <textarea id="biography" name="biography" rows="6" required placeholder="Tell buyers about your work and experience" class="{{ $input }}">{{ old('biography') }}</textarea>
                    @error('biography')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
        <div class="flex items-start gap-3 rounded-lg border border-outline-variant/50 bg-surface p-4 text-sm text-on-surface-variant">
            <span class="material-symbols-outlined text-primary">verified_user</span>
            <p>Applications are manually verified. Your legal name and address are kept private. Once approved, buyers see your vendor name, and your business name if you provide one.</p>
        </div>
        <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Submit for verification</button>
    </form>
</div>
</x-marketplace-layout>
