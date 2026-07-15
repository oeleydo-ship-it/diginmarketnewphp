<x-marketplace-layout title="Sales — Seller Studio">
<div class="mx-auto max-w-7xl px-6 py-10">
    <div class="mb-6">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Sales</h1>
    </div>
    <x-seller-nav />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5"><p class="font-display text-2xl font-bold">{{ number_format($totals['count']) }}</p><p class="text-sm text-on-surface-variant">Units sold</p></div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5"><p class="font-display text-2xl font-bold">${{ number_format($totals['gross'], 2) }}</p><p class="text-sm text-on-surface-variant">Gross revenue</p></div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5"><p class="font-display text-2xl font-bold text-secondary">${{ number_format($totals['earnings'], 2) }}</p><p class="text-sm text-on-surface-variant">Your earnings</p></div>
    </div>

    <form method="GET" class="mb-5 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Order # or customer" class="min-w-[200px] flex-1 rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
        <select name="product" class="rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
            <option value="">All products</option>
            @foreach($products as $p)<option value="{{ $p->id }}" @selected((string) request('product') === (string) $p->id)>{{ $p->title }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary hover:opacity-90">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest">
        <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-surface-container text-xs uppercase tracking-[.08em] text-on-surface-variant"><tr><th class="px-5 py-4">Product</th><th class="px-5 py-4">Customer</th><th class="px-5 py-4">Order</th><th class="px-5 py-4">Date</th><th class="px-5 py-4 text-right">Gross</th><th class="px-5 py-4 text-right">Earned</th></tr></thead>
            <tbody class="divide-y divide-outline-variant">
            @forelse($items as $item)
                <tr>
                    <td class="px-5 py-4"><span class="font-semibold">{{ $item->product_title }}</span><span class="ml-2 rounded bg-surface-container-high px-2 py-0.5 font-mono text-[10px] uppercase">{{ $item->license_name }}</span></td>
                    <td class="px-5 py-4 text-on-surface-variant">{{ $item->order?->user?->name ?? '—' }}</td>
                    <td class="px-5 py-4 font-mono text-xs text-on-surface-variant">{{ $item->order?->number }}</td>
                    <td class="px-5 py-4 text-on-surface-variant">{{ $item->order?->paid_at?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-5 py-4 text-right font-semibold">${{ number_format((float) $item->total, 2) }}</td>
                    <td class="px-5 py-4 text-right font-semibold text-secondary">${{ number_format((float) $item->seller_earning, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-16 text-center text-on-surface-variant"><span class="material-symbols-outlined mb-2 block text-4xl">receipt_long</span>No sales{{ request('q') || request('product') ? ' match this filter' : ' yet' }}.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-6">{{ $items->links() }}</div>
</div>
</x-marketplace-layout>
