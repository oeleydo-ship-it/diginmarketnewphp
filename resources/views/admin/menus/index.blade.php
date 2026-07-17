<x-admin-layout title="Navigation Menus">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Navigation Menus</h1><p class="mt-2 text-[#626576]">Manage the links shown in the marketplace footer.</p></div>
 @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
 @if($errors->any())<div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
 @php($input='rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2 text-sm outline-none focus:border-[#3525cd]')
 <div class="mt-8 grid gap-6 xl:grid-cols-2">
  @foreach(['footer-legal'=>'Footer — Legal column','footer-resources'=>'Footer — Support column'] as $location=>$title)
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">list</span>{{ $title }}</h2>
   <div class="mt-4 space-y-3">
   @forelse($items[$location] ?? [] as $item)
    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-[#e2e4ec] bg-[#fafbff] p-3">
     <form method="POST" action="{{ route('admin.menus.update',$item) }}" class="flex min-w-0 flex-1 flex-wrap items-center gap-2">@csrf @method('PUT')
      <input type="hidden" name="location" value="{{ $item->location }}">
      <input name="label" value="{{ $item->label }}" required class="{{ $input }} w-40">
      <input name="url" value="{{ $item->url }}" required class="{{ $input }} min-w-0 flex-1 font-mono text-xs">
      <input name="display_order" type="number" min="0" value="{{ $item->display_order }}" class="{{ $input }} w-16" title="Order">
      <label class="flex items-center gap-1 text-xs text-[#555868]"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)>Active</label>
      <button class="rounded-lg border border-emerald-300 px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Save</button>
     </form>
     <form method="POST" action="{{ route('admin.menus.destroy',$item) }}" data-confirm="Remove this menu item?">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50">Remove</button></form>
    </div>
   @empty<p class="rounded-lg border border-dashed border-[#d7d9e5] p-6 text-center text-sm text-[#777a8a]">No items — the footer shows its default links.</p>@endforelse
   </div>
   <form method="POST" action="{{ route('admin.menus.store') }}" class="mt-4 flex flex-wrap items-center gap-2 border-t border-[#e2e4ec] pt-4">@csrf
    <input type="hidden" name="location" value="{{ $location }}">
    <input name="label" required placeholder="Label" class="{{ $input }} w-40">
    <input name="url" required placeholder="/pages/example or https://…" class="{{ $input }} min-w-0 flex-1 font-mono text-xs">
    <input name="display_order" type="number" min="0" value="{{ ($items[$location] ?? collect())->max('display_order')+1 }}" class="{{ $input }} w-16">
    <button class="rounded-lg bg-[#3525cd] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Add link</button>
   </form>
  </div>
  @endforeach
 </div>
</div>
</x-admin-layout>
