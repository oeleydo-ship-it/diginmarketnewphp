<x-admin-layout title="Blog">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div class="flex flex-wrap items-end justify-between gap-4">
  <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Blog</h1><p class="mt-2 text-[#626576]">Publish product updates, guides, and marketplace news.</p></div>
  <a href="{{ route('admin.blog.create') }}" class="flex items-center gap-2 rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]"><span class="material-symbols-outlined text-[18px]">add</span>New post</a>
 </div>
 @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
 <form method="GET" class="mt-8 flex flex-col gap-3 rounded-xl border border-[#d7d9e5] bg-white p-4 shadow-sm sm:flex-row"><label class="relative flex-1"><span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#777a8a]">search</span><input name="q" value="{{ request('q') }}" placeholder="Search title or slug" class="w-full rounded-lg border border-[#d7d9e5] bg-[#fafbff] py-2.5 pl-10 pr-4 text-sm"></label><select name="status" class="rounded-lg border border-[#d7d9e5] px-4 py-2.5 text-sm"><option value="">All statuses</option><option value="draft" @selected(request('status')==='draft')>Draft</option><option value="published" @selected(request('status')==='published')>Published</option></select><button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white">Filter</button></form>
 <div class="mt-5 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
  <div class="divide-y divide-[#e2e4ec]">
  @forelse($posts as $post)
   <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
    <div class="min-w-0">
     <p class="font-semibold">{{ $post->title }}</p>
     <p class="mt-0.5 text-sm text-[#626576]"><span class="font-mono text-xs">/blog/{{ $post->slug }}</span> · {{ $post->author?->name ?? 'Unknown author' }} · updated {{ $post->updated_at->diffForHumans() }}</p>
    </div>
    <div class="flex items-center gap-3">
     <span class="rounded-full px-3 py-1 text-xs font-bold capitalize {{ $post->status==='published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $post->status }}</span>
     @if($post->status==='published')<a class="text-sm font-semibold text-[#626576] hover:text-[#3525cd]" href="{{ route('blog.show',$post->slug) }}">View</a>@endif
     <a class="text-sm font-semibold text-[#3525cd]" href="{{ route('admin.blog.edit',$post) }}">Edit</a>
    </div>
   </div>
  @empty<div class="px-6 py-16 text-center text-[#777a8a]"><span class="material-symbols-outlined mb-2 block text-4xl">rss_feed</span>No posts yet. Share your first update.</div>@endforelse
  </div>
 </div>
 <div class="mt-6">{{ $posts->links() }}</div>
</div>
</x-admin-layout>
