<x-marketplace-layout title="Seller Studio — DiginMarket">
<div class="mx-auto max-w-7xl px-6 py-10">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Overview</h1>
        </div>
        <a href="{{ route('seller.products.create') }}" class="flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">
            <span class="material-symbols-outlined text-[20px]">add</span> New product
        </a>
    </div>

    <x-seller-nav />

    <!-- Wallet + KPIs -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php($tiles = [
            ['label' => 'Available balance', 'value' => '$'.number_format($stats['available'], 2), 'icon' => 'account_balance_wallet', 'accent' => 'text-secondary'],
            ['label' => 'Pending clearance', 'value' => '$'.number_format($stats['pending'], 2), 'icon' => 'schedule', 'accent' => 'text-primary'],
            ['label' => 'Lifetime earnings', 'value' => '$'.number_format($stats['lifetime'], 2), 'icon' => 'trending_up', 'accent' => 'text-secondary'],
            ['label' => 'Gross revenue', 'value' => '$'.number_format($stats['revenue'], 2), 'icon' => 'payments', 'accent' => 'text-primary'],
        ])
        @foreach($tiles as $t)
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5">
                <span class="material-symbols-outlined text-[22px] {{ $t['accent'] }}">{{ $t['icon'] }}</span>
                <p class="mt-2 font-display text-2xl font-bold">{{ $t['value'] }}</p>
                <p class="text-sm text-on-surface-variant">{{ $t['label'] }}</p>
            </div>
        @endforeach
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php($counts = [
            ['label' => 'Sales', 'value' => number_format($stats['sales']), 'icon' => 'receipt_long'],
            ['label' => 'Customers', 'value' => number_format($stats['customers']), 'icon' => 'group'],
            ['label' => 'Published products', 'value' => number_format($stats['products_published']), 'icon' => 'inventory_2'],
            ['label' => 'Awaiting review', 'value' => number_format($stats['products_pending']), 'icon' => 'hourglass_top'],
        ])
        @foreach($counts as $c)
            <div class="flex items-center gap-4 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-container/25 text-primary"><span class="material-symbols-outlined">{{ $c['icon'] }}</span></span>
                <div>
                    <p class="font-display text-xl font-bold">{{ $c['value'] }}</p>
                    <p class="text-sm text-on-surface-variant">{{ $c['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <!-- Recent sales -->
        <section class="lg:col-span-2 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold">Recent sales</h2>
                <a href="{{ route('seller.sales') }}" class="text-sm font-semibold text-primary hover:underline">View all</a>
            </div>
            @forelse($recentSales as $item)
                <div class="flex items-center justify-between gap-3 border-t border-outline-variant py-3 first:border-t-0">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $item->product_title }}</p>
                        <p class="text-xs text-on-surface-variant">{{ $item->order?->user?->name ?? 'Customer' }} · {{ $item->order?->paid_at?->format('M j, Y') ?? '—' }}</p>
                    </div>
                    <span class="shrink-0 font-display font-bold text-secondary">+${{ number_format((float) $item->seller_earning, 2) }}</span>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-on-surface-variant">No sales yet. Your earnings will appear here.</p>
            @endforelse
        </section>
        <!-- Top products -->
        <section class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
            <h2 class="mb-4 font-display text-lg font-semibold">Top products</h2>
            @forelse($topProducts as $product)
                <a href="{{ route('seller.products.edit', $product) }}" class="flex items-center justify-between gap-3 border-t border-outline-variant py-3 first:border-t-0 hover:text-primary">
                    <span class="min-w-0 truncate text-sm font-medium">{{ $product->title }}</span>
                    <span class="shrink-0 font-mono text-xs text-on-surface-variant">{{ number_format($product->sales_count) }} sales</span>
                </a>
            @empty
                <p class="py-8 text-center text-sm text-on-surface-variant">Publish a product to see rankings.</p>
            @endforelse
        </section>
    </div>
</div>
</x-marketplace-layout>
