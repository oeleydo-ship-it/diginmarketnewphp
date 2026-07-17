<x-admin-layout title="Coupons">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Coupons</h1><p class="mt-2 text-[#626576]">Platform-wide discount codes validated server-side at checkout.</p></div>
 @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
 @if($errors->any())<div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
 @php($input='rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2 text-sm outline-none focus:border-[#3525cd]')
 @php($label='text-xs font-semibold uppercase tracking-wide text-[#555868]')
 <div class="mt-8 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">add_circle</span>Create coupon</h2>
  <form method="POST" action="{{ route('admin.coupons.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Code</label><input name="code" required placeholder="LAUNCH20" class="{{ $input }} font-mono uppercase"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Type</label><select name="type" class="{{ $input }}"><option value="percent">Percent off</option><option value="fixed">Fixed amount off</option></select></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Value</label><input name="value" required type="number" step="0.01" min="0.01" placeholder="20" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Minimum cart total</label><input name="min_cart_total" type="number" step="0.01" min="0" placeholder="No minimum" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Max total uses</label><input name="max_uses" type="number" min="1" placeholder="Unlimited" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Max uses per customer</label><input name="max_uses_per_user" type="number" min="1" value="1" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Starts</label><input name="starts_at" type="date" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Ends</label><input name="ends_at" type="date" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1 sm:col-span-2 lg:col-span-3"><label class="{{ $label }}">Description</label><input name="description" placeholder="Internal note" class="{{ $input }}"></div>
   <button class="self-end rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Create coupon</button>
  </form>
 </div>
 <form method="GET" class="mt-8 flex max-w-md gap-2"><input name="q" value="{{ request('q') }}" placeholder="Search code" class="{{ $input }} flex-1 bg-white"><button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white">Search</button></form>
 <div class="mt-5 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
  <div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm">
   <thead class="bg-[#edf2ff] text-xs uppercase tracking-[.08em] text-[#424555]"><tr><th class="px-5 py-4">Code</th><th class="px-5 py-4">Scope</th><th class="px-5 py-4">Discount</th><th class="px-5 py-4">Rules</th><th class="px-5 py-4">Usage</th><th class="px-5 py-4">Window</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Actions</th></tr></thead>
   <tbody class="divide-y divide-[#e2e4ec]">
   @forelse($coupons as $coupon)
    <tr class="transition hover:bg-[#fafbff]">
     <td class="px-5 py-4"><span class="font-mono font-bold text-[#251bd5]">{{ $coupon->code }}</span>@if($coupon->description)<p class="mt-0.5 text-xs text-[#777a8a]">{{ $coupon->description }}</p>@endif</td>
     <td class="px-5 py-4 text-[#626576]">{{ $coupon->seller?->name ?? 'Platform' }}</td>
     <td class="px-5 py-4 font-semibold">{{ $coupon->type==='percent' ? rtrim(rtrim(number_format($coupon->value,2),'0'),'.').'%' : '$'.number_format($coupon->value,2) }}</td>
     <td class="px-5 py-4 text-[#626576]">@if($coupon->min_cart_total)Min ${{ number_format($coupon->min_cart_total,2) }} · @endif{{ $coupon->max_uses_per_user }}/customer</td>
     <td class="px-5 py-4">{{ number_format($coupon->used_count) }}{{ $coupon->max_uses ? ' / '.number_format($coupon->max_uses) : '' }}</td>
     <td class="px-5 py-4 text-[#626576]">{{ $coupon->starts_at?->format('M j') ?? '—' }} → {{ $coupon->ends_at?->format('M j, Y') ?? 'no end' }}</td>
     <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $coupon->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
     <td class="px-5 py-4">
      <div class="flex justify-end gap-2">
       <form method="POST" action="{{ route('admin.coupons.toggle',$coupon) }}">@csrf @method('PUT')<button class="rounded-lg border border-[#d7d9e5] px-3 py-1.5 text-xs font-semibold hover:bg-[#f4f6fd]">{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}</button></form>
       @if(!$coupon->usages_count)<form method="POST" action="{{ route('admin.coupons.destroy',$coupon) }}" data-confirm="Delete coupon {{ $coupon->code }}?">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button></form>@endif
      </div>
     </td>
    </tr>
   @empty<tr><td colspan="8" class="px-6 py-16 text-center text-[#777a8a]"><span class="material-symbols-outlined mb-2 block text-4xl">sell</span>No coupons yet. Create your first discount code above.</td></tr>@endforelse
   </tbody>
  </table></div>
 </div>
 <div class="mt-6">{{ $coupons->links() }}</div>
</div>
</x-admin-layout>
