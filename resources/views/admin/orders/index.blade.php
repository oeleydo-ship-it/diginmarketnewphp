<x-marketplace-layout title="Orders">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Orders</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 <form method="GET" class="mt-8 flex flex-wrap gap-3">
  <input name="q" value="{{ request('q') }}" placeholder="Order number" class="rounded-lg bg-white/5 p-2">
  <select name="status" class="rounded-lg bg-slate-900 p-2"><option value="">Any payment status</option>@foreach(['pending','paid','partially_refunded','failed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select>
  <button class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Filter</button>
 </form>
 <div class="mt-6 space-y-3">
 @forelse($orders as $order)
  <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
   <div>
    <p class="font-semibold">{{ $order->number }}</p>
    <p class="text-sm text-slate-400">{{ $order->user?->name }} · {{ $order->items->count() }} {{ str('item')->plural($order->items->count()) }} · {{ $order->created_at->format('M j, Y H:i') }}</p>
   </div>
   <div class="text-right"><p class="font-bold">${{ number_format($order->total,2) }} {{ $order->currency }}</p><p class="text-sm text-slate-400">{{ $order->payment_status }} · {{ $order->status }}</p></div>
  </div>
 @empty<p class="text-slate-400">No orders match.</p>@endforelse
 </div>
 <div class="mt-6">{{ $orders->links() }}</div>
</div>
</x-marketplace-layout>
