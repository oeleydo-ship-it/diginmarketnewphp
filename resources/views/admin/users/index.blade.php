<x-marketplace-layout title="User directory">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">User directory</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 <form method="GET" class="mt-8 flex flex-wrap gap-3">
  <input name="q" value="{{ request('q') }}" placeholder="Search name or email" class="rounded-lg bg-white/5 p-2">
  <select name="role" class="rounded-lg bg-slate-900 p-2"><option value="">Any role</option><option value="administrator" @selected(request('role')==='administrator')>Administrator</option><option value="seller" @selected(request('role')==='seller')>Seller</option><option value="customer" @selected(request('role')==='customer')>Customer</option></select>
  <button class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Filter</button>
 </form>
 <div class="mt-6 space-y-3">
 @forelse($users as $user)
  <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
   <div>
    <p class="font-semibold">{{ $user->name }} <span class="text-sm text-slate-400">{{ $user->email }}</span></p>
    <p class="text-sm text-slate-400">{{ $user->roles->pluck('slug')->join(', ') ?: 'customer' }} · joined {{ $user->created_at->format('M j, Y') }} · <span class="{{ $user->status==='active'?'text-emerald-300':'text-rose-300' }}">{{ $user->status }}</span></p>
   </div>
   @if($user->id!==auth()->id())
   <form method="POST" action="{{ route('admin.users.status',$user) }}">@csrf @method('PUT')<input type="hidden" name="status" value="{{ $user->status==='active'?'suspended':'active' }}"><button class="rounded-lg border {{ $user->status==='active'?'border-rose-400 text-rose-300':'border-emerald-400 text-emerald-300' }} px-4 py-2 font-bold">{{ $user->status==='active'?'Suspend':'Reactivate' }}</button></form>
   @endif
  </div>
 @empty<p class="text-slate-400">No users match.</p>@endforelse
 </div>
 <div class="mt-6">{{ $users->links() }}</div>
</div>
</x-marketplace-layout>
