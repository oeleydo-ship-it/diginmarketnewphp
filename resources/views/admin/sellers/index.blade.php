<x-admin-layout title="Seller Applications">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-end">
  <div>
   <h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Seller Applications</h1>
   <p class="mt-2 text-[#626576]">Review creator profiles before granting seller access.</p>
  </div>
  <div class="flex flex-wrap items-center gap-3">
   <span class="rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800">{{ $sellers->total() }} pending</span>
   <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">{{ $approved->count() }} approved</span>
  </div>
 </div>

 <section class="mt-8">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
   <div>
    <h2 class="text-2xl font-extrabold tracking-tight">Pending applications</h2>
    <p class="mt-1 text-sm text-[#626576]">Scan applicants quickly, then approve instantly or open an inline rejection note.</p>
   </div>
  </div>

  <div class="mt-5 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
   @if($sellers->isEmpty())
    <div class="px-6 py-16 text-center text-[#626576]">
     <span class="material-symbols-outlined mb-3 block text-5xl text-emerald-600">verified_user</span>
     <h2 class="font-bold text-[#111827]">Applications are up to date</h2>
     <p class="mt-1 text-sm">No seller applications are waiting for review.</p>
    </div>
   @else
    <div class="overflow-x-auto">
     <table class="w-full min-w-[1100px] text-left text-sm">
      <thead class="bg-[#edf2ff] text-xs uppercase tracking-[.08em] text-[#424555]">
       <tr>
        <th class="px-5 py-4">Avatar / Author</th>
        <th class="px-5 py-4">Username</th>
        <th class="px-5 py-4">Full legal name</th>
        <th class="px-5 py-4">Business name</th>
        <th class="px-5 py-4">Country</th>
        <th class="px-5 py-4">Submitted / Status</th>
        <th class="px-5 py-4 text-right">Actions</th>
       </tr>
      </thead>
      <tbody class="divide-y divide-[#e2e4ec]">
       @foreach($sellers as $profile)
        <tr class="align-top hover:bg-[#fafbff]">
         <td class="px-5 py-4">
          <div class="flex items-start gap-3">
           <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e9e7ff] text-sm font-bold text-[#3525cd]">{{ str($profile->display_name)->substr(0,1)->upper() }}</span>
           <div class="min-w-0">
            <p class="truncate font-semibold text-[#111827]">{{ $profile->display_name }}</p>
            <p class="truncate text-xs text-[#777a8a]">{{ $profile->user->email }}</p>
           </div>
          </div>
         </td>
         <td class="px-5 py-4 font-medium text-[#3525cd]">{{ '@'.$profile->username }}</td>
         <td class="px-5 py-4 text-[#525565]">{{ $profile->full_name ?? '—' }}</td>
         <td class="px-5 py-4 text-[#626576]">
          <p>{{ $profile->business_name ?? '—' }}</p>
          @if($profile->website)
           <a href="{{ $profile->website }}" target="_blank" rel="noopener" class="mt-1 block truncate text-xs text-[#3525cd] hover:underline">{{ $profile->website }}</a>
          @endif
         </td>
         <td class="px-5 py-4 text-[#626576]">
          <p>{{ $profile->country ?? '—' }}</p>
          @if($profile->city)
           <p class="mt-1 text-xs text-[#777a8a]">{{ $profile->city }}</p>
          @endif
         </td>
         <td class="px-5 py-4 text-[#626576]">
          <p class="font-medium text-[#111827]">{{ optional($profile->created_at)->format('M j, Y') ?? '—' }}</p>
          <div class="mt-2 flex flex-wrap items-center gap-2">
           <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">Pending</span>
           @if($profile->phone)
            <span class="text-xs text-[#777a8a]">{{ $profile->phone }}</span>
           @endif
          </div>
         </td>
         <td class="px-5 py-4">
          <div class="flex justify-end gap-2">
           <form method="POST" action="{{ route('admin.sellers.approve',$profile) }}">
            @csrf
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button>
           </form>
           <button
            type="button"
            data-seller-reject-toggle="{{ $profile->id }}"
            aria-expanded="false"
            class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
           >
            Reject
           </button>
          </div>
         </td>
        </tr>
        <tr class="hidden bg-[#fcfcff]" data-seller-reject-panel="{{ $profile->id }}">
         <td colspan="7" class="px-5 py-4">
          <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_auto] lg:items-end">
           <div class="grid gap-3 md:grid-cols-3">
            <div>
             <p class="text-xs uppercase tracking-[.08em] text-[#777a8a]">Verification address</p>
             <p class="mt-1 text-sm text-[#525565]">{{ collect([$profile->address,$profile->city,$profile->postal_code,$profile->country])->filter()->join(', ') ?: '—' }}</p>
            </div>
            <div class="md:col-span-2">
             <p class="text-xs uppercase tracking-[.08em] text-[#777a8a]">Bio</p>
             <p class="mt-1 text-sm leading-6 text-[#525565]">{{ $profile->biography }}</p>
            </div>
           </div>
           <form method="POST" action="{{ route('admin.sellers.reject',$profile) }}" class="grid gap-3 sm:grid-cols-[minmax(280px,1fr)_auto]">
            @csrf
            <label class="block">
             <span class="sr-only">Reason for rejection for {{ $profile->display_name }}</span>
             <input
              name="reason"
              required
              placeholder="Reason for rejection"
              class="w-full rounded-lg border border-[#d7d9e5] px-3 py-2.5 text-sm"
             >
            </label>
            <div class="flex gap-2">
             <button class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Submit rejection</button>
             <button type="button" data-seller-reject-close="{{ $profile->id }}" class="rounded-lg border border-[#d7d9e5] px-4 py-2.5 text-sm font-semibold text-[#555868] hover:bg-[#f4f6fd]">Cancel</button>
            </div>
           </form>
          </div>
         </td>
        </tr>
       @endforeach
      </tbody>
     </table>
    </div>
   @endif
  </div>

  <div class="mt-6">{{ $sellers->links() }}</div>
 </section>

 <section class="mt-12">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
   <div>
    <h2 class="text-2xl font-extrabold tracking-tight">Approved authors</h2>
    <p class="mt-1 text-sm text-[#626576]">Feature standout authors to badge their storefront and surface their work.</p>
   </div>
   <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">{{ $approved->where('is_featured',true)->count() }} featured</span>
  </div>

  <div class="mt-5 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
   <div class="overflow-x-auto">
    <table class="w-full min-w-[760px] text-left text-sm">
     <thead class="bg-[#edf2ff] text-xs uppercase tracking-[.08em] text-[#424555]">
      <tr>
       <th class="px-5 py-4">Author</th>
       <th class="px-5 py-4">Username</th>
       <th class="px-5 py-4">Business</th>
       <th class="px-5 py-4">Country</th>
       <th class="px-5 py-4">Status</th>
       <th class="px-5 py-4 text-right">Featured</th>
      </tr>
     </thead>
     <tbody class="divide-y divide-[#e2e4ec]">
      @forelse($approved as $profile)
       <tr class="hover:bg-[#fafbff]">
        <td class="px-5 py-4">
         <div class="flex items-center gap-3">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e9e7ff] text-sm font-bold text-[#3525cd]">{{ str($profile->display_name)->substr(0,1)->upper() }}</span>
          <div>
           <a href="{{ route('sellers.show',$profile->username) }}" target="_blank" rel="noopener" class="font-semibold text-[#251bd5] hover:underline">{{ $profile->display_name }}</a>
           <p class="text-xs text-[#777a8a]">{{ $profile->user->email }}</p>
          </div>
         </div>
        </td>
        <td class="px-5 py-4 font-medium text-[#3525cd]">{{ '@'.$profile->username }}</td>
        <td class="px-5 py-4 text-[#626576]">{{ $profile->business_name ?? '—' }}</td>
        <td class="px-5 py-4 text-[#626576]">{{ $profile->country ?? '—' }}</td>
        <td class="px-5 py-4">
         @if($profile->is_featured)
          <span class="rounded-full bg-[#3525cd] px-3 py-1 text-xs font-bold text-white">Featured</span>
         @else
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Standard</span>
         @endif
        </td>
        <td class="px-5 py-4 text-right">
         <form method="POST" action="{{ route('admin.sellers.feature',$profile) }}">
          @csrf
          @method('PUT')
          <button class="rounded-lg border px-4 py-1.5 text-xs font-semibold {{ $profile->is_featured ? 'border-[#d7d9e5] text-[#555868] hover:bg-[#f4f6fd]' : 'border-[#3525cd] text-[#3525cd] hover:bg-[#3525cd]/10' }}">{{ $profile->is_featured ? 'Unfeature' : 'Feature' }}</button>
         </form>
        </td>
       </tr>
      @empty
       <tr>
        <td colspan="6" class="px-6 py-12 text-center text-[#777a8a]">No approved authors yet.</td>
       </tr>
      @endforelse
     </tbody>
    </table>
   </div>
  </div>
 </section>
</div>
</x-admin-layout>
