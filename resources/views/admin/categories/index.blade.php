<x-admin-layout title="Categories">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">

 <div class="flex items-center justify-between">
  <div>
   <h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Categories</h1>
   <p class="mt-1 text-sm text-[#626576]">Organise the catalog. Images and icons show on the homepage and category pages.</p>
  </div>
  <button data-toggle-create class="flex items-center gap-1.5 rounded-lg bg-[#3525cd] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">
   <span class="material-symbols-outlined text-[18px]">add</span>New category
  </button>
 </div>

 @if(session('status'))<div class="mt-4 flex gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><span class="material-symbols-outlined">check_circle</span>{{ session('status') }}</div>@endif
 @if($errors->any())<div class="mt-4 flex gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><span class="material-symbols-outlined">error</span>{{ $errors->first() }}</div>@endif

 @php($input='w-full rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]')
 @php($label='text-xs font-semibold uppercase tracking-wide text-[#555868]')

 {{-- ── Create form (collapsible) ── --}}
 <div data-create-panel class="{{ $errors->any() ? '' : 'hidden' }} mt-4 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  <h2 class="mb-4 flex items-center gap-2 text-base font-bold"><span class="material-symbols-outlined text-[#3525cd]">add_circle</span>New category</h2>
  <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data"
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Name</label><input name="name" required value="{{ old('name') }}" placeholder="e.g. PHP Scripts" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Icon <a href="https://fonts.google.com/icons" target="_blank" rel="noopener" class="font-normal normal-case text-[#3525cd]">(browse)</a></label><input name="icon" value="{{ old('icon') }}" placeholder="auto-picked from name" class="{{ $input }} font-mono"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Display order</label><input name="display_order" type="number" min="0" value="{{ old('display_order', 0) }}" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Commission % override</label><input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ old('commission_rate') }}" placeholder="Default" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1 sm:col-span-2"><label class="{{ $label }}">Description</label><input name="description" value="{{ old('description') }}" placeholder="Short blurb shown on category page" class="{{ $input }}"></div>
   <div class="flex flex-col gap-1"><label class="{{ $label }}">Image <span class="font-normal normal-case text-[#777a8a]">(JPG/PNG/WebP/SVG · 2 MB)</span></label><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="text-sm"></div>
   <div class="flex items-end gap-4">
    <label class="flex items-center gap-2 text-sm text-[#555868]"><input type="checkbox" name="is_active" value="1" checked class="rounded text-[#3525cd]">Active</label>
    <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Create category</button>
   </div>
  </form>
 </div>

 {{-- ── Categories table ── --}}
 <div class="mt-6 overflow-hidden rounded-xl border border-[#d7d9e5] bg-white shadow-sm">
  @if($categories->isEmpty())
   <div class="p-16 text-center text-[#777a8a]"><span class="material-symbols-outlined mb-2 block text-4xl">category</span>No categories yet. Create your first one above.</div>
  @else
   <table class="w-full text-sm">
    <thead>
     <tr class="border-b border-[#e2e4ec] bg-[#f7f8fc] text-xs font-semibold uppercase tracking-wide text-[#555868]">
      <th class="px-4 py-3 text-left w-16">Thumb</th>
      <th class="px-4 py-3 text-left">Name</th>
      <th class="px-4 py-3 text-left hidden sm:table-cell">Slug</th>
      <th class="px-4 py-3 text-center hidden md:table-cell">Products</th>
      <th class="px-4 py-3 text-center hidden lg:table-cell">Order</th>
      <th class="px-4 py-3 text-center">Status</th>
      <th class="px-4 py-3 text-right">Actions</th>
     </tr>
    </thead>
    <tbody class="divide-y divide-[#e2e4ec]">
     @foreach($categories as $category)
      {{-- ── Summary row ── --}}
      <tr class="group transition hover:bg-[#f7f8fc]" data-category-row="{{ $category->id }}">
       <td class="px-4 py-3">
        @if($category->imageUrl())
         <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="h-10 w-10 rounded-lg object-cover border border-[#e2e4ec]">
        @else
         <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#edf2ff]">
          <span class="material-symbols-outlined text-xl text-[#3525cd]">{{ $category->displayIcon() }}</span>
         </span>
        @endif
       </td>
       <td class="px-4 py-3 font-semibold text-[#1a1b2e]">{{ $category->name }}</td>
       <td class="px-4 py-3 font-mono text-xs text-[#777a8a] hidden sm:table-cell">/{{ $category->slug }}</td>
       <td class="px-4 py-3 text-center text-[#555868] hidden md:table-cell">{{ $category->products_count }}</td>
       <td class="px-4 py-3 text-center text-[#555868] hidden lg:table-cell">{{ $category->display_order }}</td>
       <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
          {{ $category->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
         {{ $category->is_active ? 'Active' : 'Hidden' }}
        </span>
       </td>
       <td class="px-4 py-3 text-right">
        <div class="flex items-center justify-end gap-2">
         <button type="button"
                 data-expand-edit="{{ $category->id }}"
                 class="flex items-center gap-1 rounded-lg border border-[#d7d9e5] px-3 py-1.5 text-xs font-semibold text-[#3525cd] transition hover:bg-[#edf2ff]">
          <span class="material-symbols-outlined text-[14px]">edit</span>Edit
         </button>
         @if(!$category->products_count)
          <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                data-confirm="Delete category {{ $category->name }}?">@csrf @method('DELETE')
           <button class="flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">
            <span class="material-symbols-outlined text-[14px]">delete</span>Delete
           </button>
          </form>
         @endif
        </div>
       </td>
      </tr>

      {{-- ── Inline edit row (hidden by default) ── --}}
      <tr class="hidden" data-edit-panel="{{ $category->id }}">
       <td colspan="7" class="bg-[#f7f8fc] px-4 py-5">
        <div class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm">
         <div class="mb-4 flex items-center justify-between">
          <h3 class="flex items-center gap-1.5 text-sm font-bold text-[#1a1b2e]">
           <span class="material-symbols-outlined text-[16px] text-[#3525cd]">edit</span>
           Edit — {{ $category->name }}
          </h3>
          <div class="flex items-center gap-2">
           {{-- Toggle active --}}
           <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">@csrf @method('PUT')
            <button class="rounded-lg border border-[#d7d9e5] px-3 py-1.5 text-xs font-semibold text-[#555868] hover:bg-[#f4f6fd]">
             {{ $category->is_active ? 'Hide' : 'Activate' }}
            </button>
           </form>
           {{-- Remove image (if any) --}}
           @if($category->imageUrl())
            <form method="POST" action="{{ route('admin.categories.image.remove', $category) }}"
                  data-confirm="Remove the image for {{ $category->name }}?">@csrf @method('DELETE')
             <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Remove image</button>
            </form>
           @endif
           <button type="button" data-close-edit="{{ $category->id }}"
                   class="rounded-lg border border-[#d7d9e5] px-3 py-1.5 text-xs font-semibold text-[#555868] hover:bg-[#f4f6fd]">
            <span class="material-symbols-outlined text-[14px]">close</span>
           </button>
          </div>
         </div>

         <form method="POST" action="{{ route('admin.categories.update', $category) }}"
               enctype="multipart/form-data"
               class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf @method('PUT')
          <div class="flex flex-col gap-1"><label class="{{ $label }}">Name</label><input name="name" required value="{{ $category->name }}" class="{{ $input }}"></div>
          <div class="flex flex-col gap-1"><label class="{{ $label }}">Icon <a href="https://fonts.google.com/icons" target="_blank" rel="noopener" class="font-normal normal-case text-[#3525cd]">(browse)</a></label><input name="icon" value="{{ $category->icon }}" placeholder="category" class="{{ $input }} font-mono"></div>
          <div class="flex flex-col gap-1"><label class="{{ $label }}">Display order</label><input name="display_order" type="number" min="0" value="{{ $category->display_order }}" class="{{ $input }}"></div>
          <div class="flex flex-col gap-1"><label class="{{ $label }}">Commission %</label><input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ $category->commission_rate }}" placeholder="Default" class="{{ $input }}"></div>
          <div class="flex flex-col gap-1 sm:col-span-2"><label class="{{ $label }}">Description</label><input name="description" value="{{ $category->description }}" class="{{ $input }}"></div>
          <div class="flex flex-col gap-1"><label class="{{ $label }}">Replace image <span class="font-normal normal-case text-[#777a8a]">(JPG/PNG/WebP/SVG · 2 MB)</span></label><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="text-sm"></div>
          <div class="flex items-end gap-4">
           <label class="flex items-center gap-2 text-sm text-[#555868]"><input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded text-[#3525cd]">Active</label>
           <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Save changes</button>
          </div>
         </form>
        </div>
       </td>
      </tr>
     @endforeach
    </tbody>
   </table>
  @endif
 </div>

</div>
</x-admin-layout>
