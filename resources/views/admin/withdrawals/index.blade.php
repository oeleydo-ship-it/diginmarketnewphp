<x-marketplace-layout title="Withdrawal queue">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Withdrawal queue</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 @if($errors->any())<p class="mt-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-rose-300">{{ $errors->first() }}</p>@endif
 <div class="mt-8 space-y-4">
 @forelse($withdrawals as $withdrawal)
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
   <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
     <h2 class="text-xl font-bold">{{ $withdrawal->number }}</h2>
     <p class="text-slate-400">Requested {{ $withdrawal->created_at->diffForHumans() }} · Amount ${{ number_format($withdrawal->amount,2) }} · Fee ${{ number_format($withdrawal->fee,2) }} · Net ${{ number_format($withdrawal->net_amount,2) }} {{ $withdrawal->wallet?->currency }}</p>
    </div>
    <div class="flex gap-2">
     <form method="POST" action="{{ route('admin.withdrawals.approve',$withdrawal) }}">@csrf<input name="note" placeholder="Note" class="rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Pay via Stripe</button></form>
     <form method="POST" action="{{ route('admin.withdrawals.reject',$withdrawal) }}">@csrf<input name="note" placeholder="Reason" class="rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg border border-rose-400 px-4 py-2 font-bold text-rose-300">Reject</button></form>
    </div>
   </div>
  </div>
 @empty<p class="text-slate-400">No withdrawals awaiting review.</p>@endforelse
 </div>
 <div class="mt-6">{{ $withdrawals->links() }}</div>
</div>
</x-marketplace-layout>
