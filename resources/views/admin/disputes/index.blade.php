<x-admin-layout title="Dispute Resolution">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Dispute Resolution</h1><p class="mt-2 text-[#626576]">Uphold disputes with compensating ledger entries, or dismiss to reinstate the license.</p></div><span class="rounded-full bg-red-50 px-4 py-2 text-sm font-semibold text-red-700">{{ $disputes->total() }} active disputes</span></div>
 @if($errors->any())<div class="mt-5 flex gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><span class="material-symbols-outlined">error</span>{{ $errors->first() }}</div>@endif
 <div class="mt-8 space-y-5">
 @forelse($disputes as $dispute)
  <article class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm md:p-6">
   <div class="grid gap-6 xl:grid-cols-[1fr_600px]">
    <div>
     <div class="flex flex-wrap items-center gap-2">
      <h2 class="font-mono text-lg font-bold text-[#3525cd]">#{{ $dispute->number }}</h2>
      <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold capitalize text-red-700">{{ str($dispute->type)->replace('_',' ') }}</span>
      <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold capitalize text-amber-800">{{ str($dispute->status)->replace('_',' ') }}</span>
     </div>
     <p class="mt-3 text-sm text-[#626576]">{{ $dispute->user?->name ?? 'Unknown customer' }} · {{ $dispute->orderItem?->product_title }}</p>
     <p class="mt-3 rounded-lg bg-[#f6f7fb] p-4 text-sm leading-6 text-[#525565]">{{ $dispute->description }}</p>
    </div>
    <div>
     <div class="mb-4 grid grid-cols-2 gap-3 rounded-lg bg-[#edf2ff] p-4">
      <div><p class="text-xs text-[#626576]">Disputed amount</p><p class="mt-1 text-xl font-bold">${{ number_format($dispute->disputed_amount,2) }}</p></div>
      <div><p class="text-xs text-[#626576]">Opened</p><p class="mt-1 text-sm font-semibold">{{ $dispute->created_at->format('M d, Y') }}</p></div>
     </div>
     <form method="POST" action="{{ route('admin.disputes.uphold',$dispute) }}" class="grid gap-2 sm:grid-cols-[100px_1fr_auto]">@csrf
      <input name="amount" required type="number" step="0.01" min="0.01" value="{{ $dispute->disputed_amount }}" class="rounded-lg border border-[#d7d9e5] px-3 py-2 text-sm">
      <input name="decision" placeholder="Resolution note" class="min-w-0 rounded-lg border border-[#d7d9e5] px-3 py-2 text-sm">
      <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Uphold dispute</button>
     </form>
     <form method="POST" action="{{ route('admin.disputes.dismiss',$dispute) }}" class="mt-2 flex gap-2">@csrf
      <input name="decision" placeholder="Reason for dismissal" class="min-w-0 flex-1 rounded-lg border border-[#d7d9e5] px-3 py-2 text-sm">
      <button class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">Dismiss & reinstate</button>
     </form>
    </div>
   </div>
  </article>
 @empty
  <div class="rounded-xl border border-[#d7d9e5] bg-white px-6 py-16 text-center text-[#626576]"><span class="material-symbols-outlined mb-3 block text-5xl text-emerald-600">gavel</span><h2 class="font-bold text-[#111827]">No active disputes</h2><p class="mt-1 text-sm">Every dispute has been resolved.</p></div>
 @endforelse
 </div>
 <div class="mt-6">{{ $disputes->links() }}</div>
</div>
</x-admin-layout>
