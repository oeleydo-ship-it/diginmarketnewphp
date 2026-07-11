<x-marketplace-layout title="Refund queue">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Refund queue</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 @if($errors->any())<p class="mt-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-rose-300">{{ $errors->first() }}</p>@endif
 <div class="mt-8 space-y-4">
 @forelse($refunds as $refund)
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
   <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
     <h2 class="text-xl font-bold">{{ $refund->number }}</h2>
     <p class="text-slate-400">{{ $refund->user?->name }} · {{ $refund->orderItem?->product_title }} · Requested ${{ number_format($refund->requested_amount,2) }} · {{ $refund->reason }}</p>
     <p class="mt-2 max-w-2xl text-sm text-slate-300">{{ $refund->description }}</p>
    </div>
    <div class="flex gap-2">
     <form method="POST" action="{{ route('admin.refunds.approve',$refund) }}">@csrf<input name="amount" required type="number" step="0.01" min="0.01" value="{{ $refund->requested_amount }}" class="w-28 rounded-lg bg-white/5 p-2"><input name="decision" placeholder="Decision" class="ml-2 rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Refund</button></form>
     <form method="POST" action="{{ route('admin.refunds.reject',$refund) }}">@csrf<input name="decision" placeholder="Reason" class="rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg border border-rose-400 px-4 py-2 font-bold text-rose-300">Reject</button></form>
    </div>
   </div>
  </div>
 @empty<p class="text-slate-400">No refund requests awaiting review.</p>@endforelse
 </div>
 <div class="mt-6">{{ $refunds->links() }}</div>
</div>
</x-marketplace-layout>
