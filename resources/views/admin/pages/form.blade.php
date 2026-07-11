<x-marketplace-layout :title="$page->exists?'Edit page':'New page'">
<div class="mx-auto max-w-4xl px-6 py-12">
 <h1 class="text-4xl font-black">{{ $page->exists?'Edit page':'New page' }}</h1>
 @if($errors->any())<div class="mt-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-rose-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <form method="POST" action="{{ $page->exists?route('admin.pages.update',$page):route('admin.pages.store') }}" class="mt-8 space-y-4">
  @csrf @if($page->exists)@method('PUT')@endif
  <div><label class="text-sm text-slate-300">Title</label><input name="title" required value="{{ old('title',$page->title) }}" class="mt-1 w-full rounded-lg bg-white/5 p-3"></div>
  <div><label class="text-sm text-slate-300">Slug</label><input name="slug" required value="{{ old('slug',$page->slug) }}" class="mt-1 w-full rounded-lg bg-white/5 p-3"></div>
  <div><label class="text-sm text-slate-300">Excerpt</label><input name="excerpt" value="{{ old('excerpt',$page->excerpt) }}" class="mt-1 w-full rounded-lg bg-white/5 p-3"></div>
  <div><label class="text-sm text-slate-300">Body</label><textarea name="body" required rows="14" class="mt-1 w-full rounded-lg bg-white/5 p-3">{{ old('body',$page->body) }}</textarea></div>
  <div class="grid gap-4 sm:grid-cols-2">
   <div><label class="text-sm text-slate-300">Meta title</label><input name="meta_title" value="{{ old('meta_title',$page->meta_title) }}" class="mt-1 w-full rounded-lg bg-white/5 p-3"></div>
   <div><label class="text-sm text-slate-300">Meta description</label><input name="meta_description" value="{{ old('meta_description',$page->meta_description) }}" class="mt-1 w-full rounded-lg bg-white/5 p-3"></div>
  </div>
  <div><label class="text-sm text-slate-300">Status</label><select name="status" class="mt-1 w-full rounded-lg bg-slate-900 p-3"><option value="draft" @selected(old('status',$page->status)==='draft')>Draft</option><option value="published" @selected(old('status',$page->status)==='published')>Published</option></select></div>
  <button class="rounded-lg bg-emerald-400 px-6 py-3 font-bold text-slate-950">{{ $page->exists?'Save changes':'Create page' }}</button>
 </form>
</div>
</x-marketplace-layout>
