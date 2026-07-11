<x-marketplace-layout title="Seller applications">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Seller applications</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 <div class="mt-8 space-y-4">
 @forelse($sellers as $profile)
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
   <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
     <h2 class="text-xl font-bold">{{ $profile->display_name }} <span class="text-sm font-normal text-slate-400">@ {{ $profile->username }}</span></h2>
     <p class="text-slate-400">{{ $profile->user->name }} · {{ $profile->user->email }} · {{ $profile->country }}</p>
     <p class="mt-2 max-w-2xl text-sm text-slate-300">{{ $profile->biography }}</p>
    </div>
    <div class="flex gap-2">
     <form method="POST" action="{{ route('admin.sellers.approve',$profile) }}">@csrf<button class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Approve</button></form>
     <form method="POST" action="{{ route('admin.sellers.reject',$profile) }}">@csrf<input name="reason" required placeholder="Rejection reason" class="rounded-lg bg-white/5 p-2"><button class="ml-2 rounded-lg border border-rose-400 px-4 py-2 font-bold text-rose-300">Reject</button></form>
    </div>
   </div>
  </div>
 @empty<p class="text-slate-400">No pending applications.</p>@endforelse
 </div>
 <div class="mt-6">{{ $sellers->links() }}</div>
</div>
</x-marketplace-layout>
