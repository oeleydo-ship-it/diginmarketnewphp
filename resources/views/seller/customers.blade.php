<x-marketplace-layout title="Customers — Seller Studio">
<div class="mx-auto max-w-7xl px-6 py-10">
    <div class="mb-6">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Customers</h1>
    </div>
    <x-seller-nav />

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5"><p class="font-display text-2xl font-bold">{{ number_format($summary['customers']) }}</p><p class="text-sm text-on-surface-variant">Unique customers</p></div>
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5"><p class="font-display text-2xl font-bold text-secondary">{{ number_format($summary['repeat']) }}</p><p class="text-sm text-on-surface-variant">Repeat buyers</p></div>
    </div>

    <form method="GET" class="mb-5 flex gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search name or email" class="min-w-[200px] flex-1 rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
        <button class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary hover:opacity-90">Search</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest">
        <div class="overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-surface-container text-xs uppercase tracking-[.08em] text-on-surface-variant"><tr><th class="px-5 py-4">Customer</th><th class="px-5 py-4 text-right">Purchases</th><th class="px-5 py-4 text-right">Total spent</th><th class="px-5 py-4">Last purchase</th></tr></thead>
            <tbody class="divide-y divide-outline-variant">
            @forelse($rows as $row)
                <tr>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-container/30 text-sm font-bold text-primary">{{ str($row->name)->substr(0, 1)->upper() }}</span>
                            <div class="min-w-0"><p class="truncate font-semibold">{{ $row->name }}</p><p class="truncate text-xs text-on-surface-variant">{{ $row->email }}</p></div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-right font-semibold">{{ number_format($row->purchases) }}</td>
                    <td class="px-5 py-4 text-right font-semibold text-secondary">${{ number_format((float) $row->spent, 2) }}</td>
                    <td class="px-5 py-4 text-on-surface-variant">{{ $row->last_purchase ? \Illuminate\Support\Carbon::parse($row->last_purchase)->format('M j, Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-16 text-center text-on-surface-variant"><span class="material-symbols-outlined mb-2 block text-4xl">group</span>No customers{{ request('q') ? ' match this search' : ' yet' }}.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-6">{{ $rows->links() }}</div>
</div>
</x-marketplace-layout>
