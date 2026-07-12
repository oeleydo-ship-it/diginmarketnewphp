<x-marketplace-layout title="Become a Seller — DiginMarket">
<div class="mx-auto max-w-2xl px-6 py-16">
    <header class="mb-10">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Onboarding</p>
        <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">Open your storefront</h1>
        <p class="mt-3 text-on-surface-variant">Tell us about yourself. Applications are reviewed by our team before your storefront goes live.</p>
    </header>
    <form method="POST" action="{{ route('seller.apply.store') }}" class="grid gap-5 rounded-xl border border-outline-variant bg-surface-container-lowest p-8 sm:grid-cols-2">
        @csrf
        @foreach(['display_name' => 'Display name', 'username' => 'Seller username', 'country' => 'Country code', 'phone' => 'Phone', 'business_name' => 'Business name', 'website' => 'Website'] as $field => $label)
            <div class="flex flex-col gap-1.5">
                <label for="{{ $field }}" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}"
                    class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
        @endforeach
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label for="biography" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Biography</label>
            <textarea id="biography" name="biography" rows="6" required
                class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('biography') }}</textarea>
        </div>
        @if($errors->any())
            <div class="rounded-lg border border-error/30 bg-error-container/40 p-4 text-sm font-medium text-on-error-container sm:col-span-2">{{ $errors->first() }}</div>
        @endif
        <button class="rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95 sm:col-span-2">Submit for review</button>
    </form>
</div>
</x-marketplace-layout>
