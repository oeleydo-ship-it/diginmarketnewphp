<x-admin-layout title="Categories">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Categories</h1><p class="mt-2 text-[#626576]">Organise the catalog. Images and icons show on the homepage and category pages.</p></div>
 @if(session('status'))<div class="mt-5 flex gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><span class="material-symbols-outlined">check_circle</span>{{ session('status') }}</div>@endif
 @if($errors->any())<div class="mt-5 flex gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><span class="material-symbols-outlined">error</span>{{ $errors->first() }}</div>@endif
 @php($input='w-full rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]')
 @php($label='text-xs font-semibold uppercase tracking-wide text-[#555868]')

 <div class="mt-8 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">add_circle</span>New category</h2>
  <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Name</label><input name="name" required value="{{ old('name') }}" placeholder="e.g. PHP Scripts" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Material icon <a href="https://fonts.google.com/icons" target="_blank" rel="noopener" class="font-normal normal-case text-[#3525cd]">(browse)</a></label><input name="icon" value="{{ old('icon') }}" placeholder="e.g. code" class="{{ $input }} font-mono"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Display order</label><input name="display_order" type="number" min="0" value="{{ old('display_order', 0) }}" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Commission % override</label><input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ old('commission_rate') }}" placeholder="Default" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1 sm:col-span-2"><label class="{{ $label }}">Description</label><input name="description" value="{{ old('description') }}" placeholder="Short blurb shown on the category page" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Image <span class="font-normal normal-case text-[#777a8a]">(JPG/PNG/WebP/SVG · 2 MB)</span></label><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="text-sm"></div>
   <label class="flex items-center gap-2 self-end text-sm text-[#555868]"><input type="checkbox" name="is_active" value="1" checked class="rounded text-[#3525cd]">Active</label>
   <button class="self-end rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Create category</button>
  </form>
 </div>

 <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
  @forelse($categories as $category)
   <div class="flex flex-col overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
    <div class="relative flex h-32 items-center justify-center overflow-hidden bg-[#edf2ff]">
     @if($category->imageUrl())
      <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="h-full w-full object-cover">
      <form method="POST" action="{{ route('admin.categories.image.remove', $category) }}" class="absolute right-2 top-2" data-confirm="Remove this image?">@csrf @method('DELETE')<button class="flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-red-600 shadow-sm hover:bg-red-600 hover:text-white" aria-label="Remove image"><span class="material-symbols-outlined text-[16px]">delete</span></button></form>
     @else
      <span class="material-symbols-outlined text-5xl text-[#3525cd]">{{ $category->icon ?: 'category' }}</span>
     @endif
     <span class="absolute left-2 top-2 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $category->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</span>
    </div>
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="flex flex-1 flex-col gap-3 p-5">@csrf @method('PUT')
     <div class="flex items-center justify-between"><span class="font-mono text-xs text-[#777a8a]">/{{ $category->slug }}</span><span class="text-xs font-semibold text-[#555868]">{{ $category->products_count }} product{{ $category->products_count === 1 ? '' : 's' }}</span></div>
     <div class="flex flex-col gap-1"><label class="{{ $label }}">Name</label><input name="name" required value="{{ $category->name }}" class="{{ $input }}"></div>
     <div class="grid grid-cols-2 gap-3">
      <div class="flex flex-col gap-1"><label class="{{ $label }}">Icon</label><input name="icon" value="{{ $category->icon }}" placeholder="category" class="{{ $input }} font-mono"></div>
      <div class="flex flex-col gap-1"><label class="{{ $label }}">Order</label><input name="display_order" type="number" min="0" value="{{ $category->display_order }}" class="{{ $input }}"></div>
     </div>
     <div class="flex flex-col gap-1"><label class="{{ $label }}">Description</label><input name="description" value="{{ $category->description }}" class="{{ $input }}"></div>
     <div class="grid grid-cols-2 gap-3">
      <div class="flex flex-col gap-1"><label class="{{ $label }}">Commission %</label><input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ $category->commission_rate }}" placeholder="Default" class="{{ $input }}"></div>
      <div class="flex flex-col gap-1"><label class="{{ $label }}">Replace image</label><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="text-xs"></div>
     </div>
     <label class="flex items-center gap-2 text-sm text-[#555868]"><input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded text-[#3525cd]">Active</label>
     <button class="mt-auto self-start rounded-lg bg-[#3525cd] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Save changes</button>
    </form>
    <div class="flex flex-wrap items-center gap-2 border-t border-[#e2e4ec] px-5 py-3">
     <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">@csrf @method('PUT')<button class="rounded-lg border border-[#d7d9e5] px-4 py-1.5 text-sm font-semibold text-[#555868] hover:bg-[#f4f6fd]">{{ $category->is_active ? 'Hide' : 'Activate' }}</button></form>
     @if(!$category->products_count)
      <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Delete category {{ $category->name }}?">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-4 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50">Delete</button></form>
     @endif
    </div>
   </div>
  @empty
   <div class="col-span-full rounded-xl border border-dashed border-[#d7d9e5] bg-white p-16 text-center text-[#777a8a]"><span class="material-symbols-outlined mb-2 block text-4xl">category</span>No categories yet. Create your first one above.</div>
  @endforelse
 </div>
</div>
</x-admin-layout>
