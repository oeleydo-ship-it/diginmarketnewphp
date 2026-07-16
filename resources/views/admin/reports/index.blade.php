<x-admin-layout title="Sales Reports">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">
  <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Sales reports</h1><p class="mt-2 text-[#626576]">Revenue, commission and sales performance for the selected period.</p></div>
  <a href="{{ route('admin.reports.export', request()->query()) }}" class="flex items-center gap-2 self-start rounded-lg border border-[#cfd2e1] bg-white px-4 py-2.5 text-sm font-semibold"><span class="material-symbols-outlined text-lg">download</span>Export line items</a>
 </div>

 <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 text-sm">
  <label class="block">From<input type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 block rounded-lg border border-[#cfd2e1] bg-white px-3 py-2"></label>
  <label class="block">To<input type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 block rounded-lg border border-[#cfd2e1] bg-white px-3 py-2"></label>
  <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 font-semibold text-white">Apply</button>
  <a href="{{ route('admin.reports.index') }}" class="px-2 py-2.5 font-semibold text-[#3525cd]">Last 30 days</a>
 </form>

 @php($cards = [
  ['Gross revenue', '$'.number_format($summary['gross_revenue'], 2), 'payments', 'text-emerald-600'],
  ['Platform commission', '$'.number_format($summary['platform_commission'], 2), 'account_balance', 'text-[#3525cd]'],
  ['Seller earnings', '$'.number_format($summary['seller_earnings'], 2), 'storefront', 'text-[#626576]'],
  ['Paid orders', number_format($summary['orders']), 'shopping_cart', 'text-[#626576]'],
  ['Items sold', number_format($summary['items_sold']), 'inventory_2', 'text-[#626576]'],
  ['Average order', '$'.number_format($summary['average_order'], 2), 'functions', 'text-[#626576]'],
  ['Unique customers', number_format($summary['unique_customers']), 'group', 'text-[#626576]'],
  ['Refund requests', number_format($summary['refund_requests']), 'assignment_return', 'text-amber-600'],
 ])
 <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  @foreach($cards as [$label, $value, $icon, $color])
   <article class="rounded-xl border border-[#cfd2e1] bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between"><p class="text-sm text-[#555868]">{{ $label }}</p><span class="material-symbols-outlined {{ $color }}">{{ $icon }}</span></div>
    <p class="mt-2 text-2xl font-bold">{{ $value }}</p>
   </article>
  @endforeach
 </section>

 <section class="mt-8 rounded-2xl border border-[#cfd2e1] bg-white p-6 shadow-sm">
  <h2 class="text-lg font-bold">Revenue — last 12 months</h2>
  <div class="mt-6 flex h-48 items-end gap-2">
   @foreach($trend as $point)
    <div class="group relative flex flex-1 flex-col items-center justify-end self-stretch">
     <div class="w-full rounded-t bg-[#3525cd]/80 transition-colors group-hover:bg-[#3525cd]" style="height: {{ max(2, round($point['revenue'] / $trendMax * 100)) }}%"></div>
     <span class="mt-2 text-[10px] font-medium text-[#66697a]">{{ $point['month'] }}</span>
     <span class="pointer-events-none absolute -top-7 hidden whitespace-nowrap rounded bg-[#1b1d29] px-2 py-1 text-[11px] font-semibold text-white group-hover:block">${{ number_format($point['revenue'], 0) }}</span>
    </div>
   @endforeach
  </div>
 </section>

 <section class="mt-8 grid gap-6 xl:grid-cols-2">
  <div class="rounded-2xl border border-[#cfd2e1] bg-white p-6 shadow-sm">
   <h2 class="text-lg font-bold">Top products</h2>
   <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[480px] text-left text-sm">
    <thead class="text-xs uppercase tracking-wide text-[#66697a]"><tr><th class="py-2">Product</th><th class="py-2 text-right">Sales</th><th class="py-2 text-right">Revenue</th><th class="py-2 text-right">Commission</th></tr></thead>
    <tbody class="divide-y divide-[#eceef5]">
     @forelse($topProducts as $row)
      <tr><td class="py-3 pr-3 font-medium">{{ $row->product_title }}</td><td class="py-3 text-right">{{ $row->sales }}</td><td class="py-3 text-right font-semibold">${{ number_format((float) $row->revenue, 2) }}</td><td class="py-3 text-right text-[#66697a]">${{ number_format((float) $row->commission, 2) }}</td></tr>
     @empty
      <tr><td colspan="4" class="py-8 text-center text-[#66697a]">No paid sales in this period.</td></tr>
     @endforelse
    </tbody>
   </table></div>
  </div>
  <div class="rounded-2xl border border-[#cfd2e1] bg-white p-6 shadow-sm">
   <h2 class="text-lg font-bold">Top sellers</h2>
   <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[480px] text-left text-sm">
    <thead class="text-xs uppercase tracking-wide text-[#66697a]"><tr><th class="py-2">Seller</th><th class="py-2 text-right">Sales</th><th class="py-2 text-right">Revenue</th><th class="py-2 text-right">Earnings</th></tr></thead>
    <tbody class="divide-y divide-[#eceef5]">
     @forelse($topSellers as $row)
      <tr><td class="py-3 pr-3 font-medium">{{ $row->seller_name }}</td><td class="py-3 text-right">{{ $row->sales }}</td><td class="py-3 text-right font-semibold">${{ number_format((float) $row->revenue, 2) }}</td><td class="py-3 text-right text-[#66697a]">${{ number_format((float) $row->earnings, 2) }}</td></tr>
     @empty
      <tr><td colspan="4" class="py-8 text-center text-[#66697a]">No paid sales in this period.</td></tr>
     @endforelse
    </tbody>
   </table></div>
  </div>
 </section>

 <section class="mt-8 grid gap-6 xl:grid-cols-2">
  <div class="rounded-2xl border border-[#cfd2e1] bg-white p-6 shadow-sm">
   <h2 class="text-lg font-bold">Revenue by payment method</h2>
   <div class="mt-4 space-y-3">
    @php($providerMax = max(1.0, (float) $byProvider->max('revenue')))
    @forelse($byProvider as $row)
     <div>
      <div class="flex items-center justify-between text-sm"><span class="font-medium capitalize">{{ str_replace('_', ' ', $row->provider) }}</span><span>${{ number_format((float) $row->revenue, 2) }} · {{ $row->orders }} orders</span></div>
      <div class="mt-1 h-2 rounded-full bg-[#edf2ff]"><div class="h-2 rounded-full bg-[#3525cd]" style="width: {{ max(2, round($row->revenue / $providerMax * 100)) }}%"></div></div>
     </div>
    @empty
     <p class="py-6 text-center text-sm text-[#66697a]">No paid orders in this period.</p>
    @endforelse
   </div>
  </div>
  <div class="rounded-2xl border border-[#cfd2e1] bg-white p-6 shadow-sm">
   <h2 class="text-lg font-bold">Revenue by license type</h2>
   <div class="mt-4 space-y-3">
    @php($licenseMax = max(1.0, (float) $byLicense->max('revenue')))
    @forelse($byLicense as $row)
     <div>
      <div class="flex items-center justify-between text-sm"><span class="font-medium">{{ $row->license_name }}</span><span>${{ number_format((float) $row->revenue, 2) }} · {{ $row->sales }} sales</span></div>
      <div class="mt-1 h-2 rounded-full bg-[#edf2ff]"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ max(2, round($row->revenue / $licenseMax * 100)) }}%"></div></div>
     </div>
    @empty
     <p class="py-6 text-center text-sm text-[#66697a]">No paid orders in this period.</p>
    @endforelse
   </div>
  </div>
 </section>
</div>
</x-admin-layout>
