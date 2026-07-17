<x-admin-layout title="Pending Earnings">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">
  <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Pending earnings</h1><p class="mt-2 text-[#626576]">Seller earnings held in the clearance window. Release an entry early once you've confirmed the payment settled at the gateway.</p></div>
  <form method="POST" action="{{ route('admin.system.clear-earnings') }}">@csrf<button class="flex items-center gap-2 rounded-lg bg-[#3525cd] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]"><span class="material-symbols-outlined text-lg">task_alt</span>Release all eligible</button></form>
 </div>

 @if(session('status'))<div class="mt-6 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif

 <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <article class="rounded-xl border border-[#cfd2e1] bg-white p-5 shadow-sm"><p class="text-sm text-[#555868]">Total pending</p><p class="mt-2 text-2xl font-bold">${{ number_format($summary['total_pending'], 2) }}</p></article>
  <article class="rounded-xl border border-[#cfd2e1] bg-white p-5 shadow-sm"><p class="text-sm text-[#555868]">Pending entries</p><p class="mt-2 text-2xl font-bold">{{ number_format($summary['entries']) }}</p></article>
  <article class="rounded-xl border border-[#cfd2e1] bg-white p-5 shadow-sm"><p class="text-sm text-[#555868]">Eligible for auto-release</p><p class="mt-2 text-2xl font-bold">{{ number_format($summary['eligible_now']) }}</p></article>
  <article class="rounded-xl border border-[#cfd2e1] bg-white p-5 shadow-sm">
   <p class="text-sm text-[#555868]">Clearance window</p>
   <p class="mt-2 text-2xl font-bold">{{ $summary['clearance_days'] }} {{ str('day')->plural($summary['clearance_days']) }}</p>
   <a href="{{ route('admin.settings.index') }}" class="mt-1 inline-block text-xs font-semibold text-[#3525cd] hover:underline">Change in Settings → Commerce</a>
  </article>
 </section>

 <section class="mt-8 overflow-hidden rounded-2xl border border-[#cfd2e1] bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="w-full min-w-[860px] text-left text-sm">
   <thead class="bg-[#edf2ff] text-xs uppercase tracking-[.08em] text-[#424555]"><tr><th class="px-5 py-4">Seller</th><th class="px-5 py-4">Amount</th><th class="px-5 py-4">Reference</th><th class="px-5 py-4">Credited</th><th class="px-5 py-4">Auto-releases</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Action</th></tr></thead>
   <tbody class="divide-y divide-[#dfe1eb]">
    @forelse($pending as $entry)
     @php($eligible = $entry->available_at && $entry->available_at->isPast())
     <tr class="transition hover:bg-[#fafbff]">
      <td class="px-5 py-4"><strong class="block font-medium">{{ $entry->wallet?->seller?->name ?? '—' }}</strong><span class="text-xs text-[#66697a]">{{ $entry->wallet?->seller?->email }}</span></td>
      <td class="px-5 py-4 font-semibold">${{ number_format((float) $entry->amount, 2) }}</td>
      <td class="px-5 py-4 font-mono text-xs">{{ $entry->reference }}</td>
      <td class="px-5 py-4 whitespace-nowrap">{{ $entry->created_at->format('M d, Y') }}</td>
      <td class="px-5 py-4 whitespace-nowrap">{{ $entry->available_at?->format('M d, Y') ?? '—' }}</td>
      <td class="px-5 py-4">
       @if($eligible)
        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold uppercase text-emerald-700">Eligible</span>
       @else
        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold uppercase text-amber-800">Holding · {{ now()->diffInDays($entry->available_at) + 1 }}d left</span>
       @endif
      </td>
      <td class="px-5 py-4 text-right">
       <form method="POST" action="{{ route('admin.earnings.release', $entry) }}" data-confirm="Release ${{ number_format((float) $entry->amount, 2) }} to {{ $entry->wallet?->seller?->name }}? Confirm the gateway payment settled first.">@csrf
        <button class="rounded-lg border border-[#3525cd] px-4 py-2 text-xs font-semibold text-[#3525cd] transition hover:bg-[#3525cd] hover:text-white">Release now</button>
       </form>
      </td>
     </tr>
    @empty
     <tr><td colspan="7" class="px-5 py-16 text-center text-[#66697a]"><span class="material-symbols-outlined mb-2 block text-4xl">task_alt</span>No pending earnings — everything has cleared.</td></tr>
    @endforelse
   </tbody>
  </table></div>
  <div class="border-t border-[#dfe1eb] bg-[#f4f7ff] px-5 py-4 text-sm">{{ $pending->onEachSide(1)->links() }}</div>
 </section>
</div>
</x-admin-layout>
