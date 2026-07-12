<x-admin-layout title="Audit Log">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Audit Log</h1><p class="mt-2 text-[#626576]">Immutable record of sensitive administrator activity.</p></div>
 <form method="GET" class="mt-8 grid gap-3 rounded-xl border border-[#d7d9e5] bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[1.5fr_1fr_1fr_1fr_1fr_auto]">
  <label class="relative"><span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#777a8a]">filter_alt</span><input name="action" value="{{ request('action') }}" placeholder="Filter by action, e.g. product." class="w-72 rounded-lg border border-[#d7d9e5] bg-white py-2.5 pl-10 pr-4 text-sm outline-none focus:border-[#3525cd]"></label>
  <select name="user" class="rounded-lg border border-[#d7d9e5] bg-white px-3 py-2.5 text-sm"><option value="">All administrators</option>@foreach($administrators as $administrator)<option value="{{ $administrator->id }}" @selected((string)request('user')===(string)$administrator->id)>{{ $administrator->name }}</option>@endforeach</select>
  <select name="entity" class="rounded-lg border border-[#d7d9e5] bg-white px-3 py-2.5 text-sm"><option value="">All entities</option>@foreach(['Product','SellerProfile','RefundRequest','WithdrawalRequest','User','Page','Setting'] as $entity)<option value="{{ $entity }}" @selected(request('entity')===$entity)>{{ str($entity)->headline() }}</option>@endforeach</select>
  <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-[#d7d9e5] px-3 py-2.5 text-sm" title="From date"><input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-[#d7d9e5] px-3 py-2.5 text-sm" title="To date">
  <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Filter</button>
  <div class="flex items-center gap-3 sm:col-span-2 xl:col-span-6">@if(request()->hasAny(['action','user','entity','from','to']))<a href="{{ route('admin.audits.index') }}" class="text-sm font-semibold text-[#3525cd]">Clear filters</a>@endif<a href="{{ route('admin.audits.export',request()->query()) }}" class="ml-auto flex items-center gap-1 text-sm font-semibold text-[#3525cd]"><span class="material-symbols-outlined text-lg">download</span>Export CSV</a></div>
 </form>
 <div class="mt-6 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
  <div class="divide-y divide-[#e2e4ec]">
  @forelse($logs as $log)
   <div class="flex items-start gap-4 px-5 py-4">
    <span class="material-symbols-outlined mt-0.5 rounded-lg bg-[#f0f2fa] p-2 text-lg text-[#626576]">history</span>
    <div class="min-w-0 flex-1">
     <div class="flex flex-wrap items-center justify-between gap-2">
      <p class="font-mono text-sm font-semibold text-[#251bd5]">{{ $log->action }}</p>
      <p class="text-xs text-[#777a8a]">{{ $log->user?->name ?? 'System' }} · {{ $log->ip_address }} · {{ $log->created_at->format('M j, Y H:i:s') }}</p>
     </div>
     <p class="mt-1 text-sm text-[#626576]">{{ class_basename((string)$log->entity_type) }} #{{ $log->entity_id }}@if($log->new_values) · {{ collect($log->new_values)->map(fn($v,$k)=>$k.': '.(is_scalar($v)?$v:json_encode($v)))->join(', ') }}@endif</p>
    </div>
   </div>
  @empty<div class="px-6 py-16 text-center text-[#777a8a]"><span class="material-symbols-outlined mb-2 block text-4xl">history</span>No audited activity.</div>@endforelse
  </div>
 </div>
 <div class="mt-6">{{ $logs->links() }}</div>
</div>
</x-admin-layout>
