<x-admin-layout :title="$post->exists?'Edit Post':'New Post'">
<div class="mx-auto max-w-4xl px-5 py-8 md:px-8 lg:py-10">
 <h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">{{ $post->exists?'Edit Post':'New Post' }}</h1>
 @if($errors->any())<div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 @php($input='mt-1 w-full rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]')
 @php($label='text-xs font-semibold uppercase tracking-wide text-[#555868]')
 <form method="POST" action="{{ $post->exists?route('admin.blog.update',$post):route('admin.blog.store') }}" class="mt-8 space-y-4 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  @csrf @if($post->exists)@method('PUT')@endif
  <div><label class="{{ $label }}">Title</label><input name="title" required value="{{ old('title',$post->title) }}" class="{{ $input }}"></div>
  <div><label class="{{ $label }}">Slug</label><input name="slug" required value="{{ old('slug',$post->slug) }}" class="{{ $input }} font-mono"></div>
  <div><label class="{{ $label }}">Excerpt</label><input name="excerpt" value="{{ old('excerpt',$post->excerpt) }}" class="{{ $input }}"></div>
  <div><label class="{{ $label }}">Body</label><textarea name="body" required rows="16" class="{{ $input }}">{{ old('body',$post->body) }}</textarea></div>
  <div class="grid gap-4 sm:grid-cols-2">
   <div><label class="{{ $label }}">Meta title</label><input name="meta_title" value="{{ old('meta_title',$post->meta_title) }}" class="{{ $input }}"></div>
   <div><label class="{{ $label }}">Meta description</label><input name="meta_description" value="{{ old('meta_description',$post->meta_description) }}" class="{{ $input }}"></div>
  </div>
  <div><label class="{{ $label }}">Status</label><select name="status" class="{{ $input }}"><option value="draft" @selected(old('status',$post->status)==='draft')>Draft</option><option value="published" @selected(old('status',$post->status)==='published')>Published</option></select></div>
  <button class="rounded-lg bg-[#3525cd] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">{{ $post->exists?'Save changes':'Create post' }}</button>
 </form>
</div>
</x-admin-layout>
