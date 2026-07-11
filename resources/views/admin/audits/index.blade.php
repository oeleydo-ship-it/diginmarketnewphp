<x-marketplace-layout title="Audit log">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Audit log</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 <form method="GET" class="mt-8 flex gap-3">
  <input name="action" value="{{ request('action') }}" placeholder="Filter by action, e.g. product." class="w-72 rounded-lg bg-white/5 p-2">
  <button class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Filter</button>
 </form>
 <div class="mt-6 space-y-2">
 @forelse($logs as $log)
  <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
   <div class="flex flex-wrap items-center justify-between gap-2">
    <p class="font-semibold">{{ $log->action }}</p>
    <p class="text-sm text-slate-400">{{ $log->user?->name ?? 'System' }} · {{ $log->ip_address }} · {{ $log->created_at->format('M j, Y H:i:s') }}</p>
   </div>
   <p class="mt-1 text-sm text-slate-400">{{ class_basename((string)$log->entity_type) }} #{{ $log->entity_id }}@if($log->new_values) · {{ collect($log->new_values)->map(fn($v,$k)=>$k.': '.(is_scalar($v)?$v:json_encode($v)))->join(', ') }}@endif</p>
  </div>
 @empty<p class="text-slate-400">No audited activity.</p>@endforelse
 </div>
 <div class="mt-6">{{ $logs->links() }}</div>
</div>
</x-marketplace-layout>
