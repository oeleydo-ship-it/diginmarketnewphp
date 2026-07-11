<x-marketplace-layout title="Administration">
<div class="mx-auto max-w-7xl px-6 py-12">
 <p class="text-emerald-400">Marketplace operations</p>
 <h1 class="mt-2 text-4xl font-black">Administrator dashboard</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6"><p class="text-sm text-slate-400">Gross revenue</p><p class="mt-2 text-3xl font-black">${{ number_format($metrics['revenue'],2) }}</p></div>
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6"><p class="text-sm text-slate-400">Paid orders</p><p class="mt-2 text-3xl font-black">{{ $metrics['paid_orders'] }}</p></div>
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6"><p class="text-sm text-slate-400">Registered users</p><p class="mt-2 text-3xl font-black">{{ $metrics['customers'] }}</p></div>
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6"><p class="text-sm text-slate-400">Published products</p><p class="mt-2 text-3xl font-black">{{ $metrics['published_products'] }}</p></div>
 </div>
 <h2 class="mt-12 text-2xl font-bold">Work queues</h2>
 <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
  <a href="{{ route('admin.sellers.index') }}" class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-sm text-slate-400">Seller applications</p><p class="mt-1 text-2xl font-black {{ $metrics['pending_sellers']?'text-amber-300':'' }}">{{ $metrics['pending_sellers'] }}</p></a>
  <a href="{{ route('admin.products.review') }}" class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-sm text-slate-400">Products to review</p><p class="mt-1 text-2xl font-black {{ $metrics['pending_products']?'text-amber-300':'' }}">{{ $metrics['pending_products'] }}</p></a>
  <a href="{{ route('admin.refunds.index') }}" class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-sm text-slate-400">Open refunds</p><p class="mt-1 text-2xl font-black {{ $metrics['open_refunds']?'text-amber-300':'' }}">{{ $metrics['open_refunds'] }}</p></a>
  <a href="{{ route('admin.withdrawals.index') }}" class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-sm text-slate-400">Pending withdrawals</p><p class="mt-1 text-2xl font-black {{ $metrics['pending_withdrawals']?'text-amber-300':'' }}">{{ $metrics['pending_withdrawals'] }}</p></a>
  <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><p class="text-sm text-slate-400">Open tickets</p><p class="mt-1 text-2xl font-black">{{ $metrics['open_tickets'] }}</p></div>
 </div>
 <div class="mt-12 grid gap-8 lg:grid-cols-2">
  <div>
   <h2 class="text-2xl font-bold">Recent orders</h2>
   <div class="mt-4 space-y-3">@forelse($recentOrders as $order)<div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3"><div><p class="font-semibold">{{ $order->number }}</p><p class="text-sm text-slate-400">{{ $order->user?->name }} · {{ $order->created_at->diffForHumans() }}</p></div><div class="text-right"><p class="font-bold">${{ number_format($order->total,2) }}</p><p class="text-sm text-slate-400">{{ $order->payment_status }}</p></div></div>@empty<p class="text-slate-400">No orders yet.</p>@endforelse</div>
  </div>
  <div>
   <h2 class="text-2xl font-bold">Recent administration</h2>
   <div class="mt-4 space-y-3">@forelse($recentAudits as $log)<div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3"><p class="font-semibold">{{ $log->action }}</p><p class="text-sm text-slate-400">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</p></div>@empty<p class="text-slate-400">No audited activity yet.</p>@endforelse</div>
  </div>
 </div>
</div>
</x-marketplace-layout>
