<x-marketplace-layout title="Pages">
<div class="mx-auto max-w-7xl px-6 py-12">
 <div class="flex flex-wrap items-center justify-between gap-4">
  <h1 class="text-4xl font-black">Content pages</h1>
  <a href="{{ route('admin.pages.create') }}" class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">New page</a>
 </div>
 <div class="mt-6">@include('admin.partials.nav')</div>
 <div class="mt-8 space-y-3">
 @forelse($pages as $page)
  <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
   <div>
    <p class="font-semibold">{{ $page->title }}</p>
    <p class="text-sm text-slate-400">/pages/{{ $page->slug }} · <span class="{{ $page->status==='published'?'text-emerald-300':'text-amber-300' }}">{{ $page->status }}</span> · updated {{ $page->updated_at->diffForHumans() }}</p>
   </div>
   <div class="flex gap-3 text-sm">
    @if($page->status==='published')<a class="text-slate-300" href="{{ route('pages.show',$page->slug) }}">View</a>@endif
    <a class="text-emerald-300" href="{{ route('admin.pages.edit',$page) }}">Edit</a>
   </div>
  </div>
 @empty<p class="text-slate-400">No pages yet. Create your terms, privacy, and help pages.</p>@endforelse
 </div>
 <div class="mt-6">{{ $pages->links() }}</div>
</div>
</x-marketplace-layout>
