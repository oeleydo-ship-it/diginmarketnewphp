<x-marketplace-layout title="My Purchases — DiginMarket">
<x-customer-panel>
    <header class="mb-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">My Purchases</h1>
        <p class="mt-1 text-on-surface-variant">Access your orders, downloads, and license information.</p>
    </header>
    <div class="space-y-4">
        @forelse($orders as $order)
            <a href="{{ route('purchases.show', $order) }}"
                class="group flex flex-col justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest p-6 transition-all hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 md:flex-row md:items-center">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-container/20 text-primary">
                        <span class="material-symbols-outlined">receipt_long</span>
                    </span>
                    <div>
                        <span class="font-mono text-sm font-medium text-primary group-hover:underline">{{ $order->number }}</span>
                        <p class="mt-0.5 text-sm text-on-surface-variant">
                            <span class="font-mono text-xs uppercase">{{ $order->created_at->format('M j, Y') }}</span>
                            · {{ $order->items_count ?? $order->items()->count() }} {{ str('item')->plural($order->items_count ?? $order->items()->count()) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-6 md:justify-end">
                    @php($paid = $order->payment_status === 'paid')
                    <span class="rounded px-2 py-1 font-mono text-[10px] font-bold uppercase tracking-wider {{ $paid ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed-variant' }}">
                        {{ str($order->payment_status)->headline() }}
                    </span>
                    <span class="font-display text-xl font-bold">${{ number_format($order->total, 2) }} <span class="text-xs font-medium text-on-surface-variant">{{ $order->currency }}</span></span>
                    <span class="material-symbols-outlined text-outline transition-transform group-hover:translate-x-1 group-hover:text-primary">arrow_forward</span>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">shopping_bag</span>
                <p class="text-on-surface-variant">No orders yet.</p>
                <a href="{{ route('products.index') }}" class="mt-4 inline-block rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90">Browse assets</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
</x-customer-panel>
</x-marketplace-layout>
