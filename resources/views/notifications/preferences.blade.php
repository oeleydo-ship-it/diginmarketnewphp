<x-marketplace-layout title="Notification Preferences — DiginMarket">
<x-customer-panel>
    <header class="mb-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">Notification Preferences</h1>
        <p class="mt-1 text-on-surface-variant">Choose how DiginMarket keeps you in the loop.</p>
    </header>
    <form method="POST" action="{{ route('notifications.preferences.update') }}" class="max-w-2xl space-y-3">
        @csrf @method('PUT')
        @foreach(['email_sales' => ['Sales and purchase emails', 'receipt_long'], 'email_product_updates' => ['Product update emails', 'update'], 'email_support' => ['Support emails', 'support_agent'], 'email_marketing' => ['Marketplace promotions', 'campaign'], 'in_app' => ['In-app notifications', 'notifications']] as $field => [$label, $icon])
            <label class="flex cursor-pointer items-center justify-between rounded-xl border border-outline-variant bg-surface-container-lowest p-4 transition-colors hover:border-primary/40">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary">{{ $icon }}</span>
                    <span class="text-[15px] font-medium">{{ $label }}</span>
                </span>
                <input type="checkbox" name="{{ $field }}" value="1" @checked($preferences->$field)
                    class="h-5 w-5 rounded border-outline-variant text-primary focus:ring-primary/20">
            </label>
        @endforeach
        <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Save preferences</button>
    </form>
</x-customer-panel>
</x-marketplace-layout>
