<x-admin-layout title="Settings">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Settings</h1><p class="mt-2 text-[#626576]">Marketplace configuration. Encrypted values are stored securely and never displayed.</p></div>
 @if($errors->any())<div class="mt-5 flex gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><span class="material-symbols-outlined">error</span>{{ $errors->first() }}</div>@endif
 @php($input='w-full rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]')
 @php($labelCls='text-xs font-semibold uppercase tracking-wide text-[#555868]')

 @php($tabBtn='flex w-full items-center gap-2.5 rounded-lg border-l-[3px] border-transparent px-4 py-3 text-left text-sm font-semibold text-[#555868] transition-colors hover:bg-[#f4f6fd] hover:text-[#3525cd]')
 <div data-tabs data-tabs-sync class="mt-8 flex flex-col gap-6 lg:flex-row lg:items-start">
  <div class="flex w-full flex-row gap-1 overflow-x-auto rounded-xl border border-[#d7d9e5] bg-white p-3 shadow-sm lg:sticky lg:top-6 lg:w-64 lg:shrink-0 lg:flex-col lg:overflow-visible" role="tablist" aria-label="Settings sections" aria-orientation="vertical">
   <button type="button" data-tab="branding" class="tab-active {{ $tabBtn }}"><span class="material-symbols-outlined text-[20px]">image</span>Branding</button>
   @foreach($sections as $sectionKey => $section)
    <button type="button" data-tab="{{ $sectionKey }}" class="{{ $tabBtn }}"><span class="material-symbols-outlined text-[20px]">{{ $section['icon'] }}</span>{{ $section['title'] }}</button>
   @endforeach
   <button type="button" data-tab="advanced" class="{{ $tabBtn }}"><span class="material-symbols-outlined text-[20px]">data_object</span>Advanced</button>
  </div>

  <div class="min-w-0 flex-1">
  <div data-tab-panel="branding" class="rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">image</span>Branding</h2>
   <div class="mt-4 flex flex-wrap items-center gap-6">
    <div class="flex h-20 w-44 items-center justify-center overflow-hidden rounded-lg border border-dashed border-[#d7d9e5] bg-[#fafbff]">
     @if($logo)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="Current logo" class="max-h-16 w-auto">@else<span class="text-sm text-[#777a8a]">No logo uploaded</span>@endif
    </div>
    <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">@csrf
     <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" required class="text-sm">
     <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Upload logo</button>
     <span class="text-xs text-[#777a8a]">PNG, JPG, WebP, or SVG · max 2 MB · shown in the marketplace header</span>
    </form>
   </div>
  </div>

  @foreach($sections as $sectionKey => $section)
   <div data-tab-panel="{{ $sectionKey }}" class="hidden rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('admin.settings.sections.update', $sectionKey) }}">@csrf
     <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">{{ $section['icon'] }}</span>{{ $section['title'] }}</h2>
     <div class="mt-5 grid content-start gap-4 sm:grid-cols-2 xl:grid-cols-3">
      @foreach($section['fields'] as $key => $field)
       <div class="flex flex-col gap-1 {{ str_contains($key, 'description') || str_contains($key, 'publishable') ? 'sm:col-span-2' : '' }}">
        <label class="{{ $labelCls }}">{{ $field['label'] }}</label>
        @if($field['type'] === 'select')
         <select name="{{ str_replace('.', '__', $key) }}" class="{{ $input }}">
          @foreach($field['options'] as $option)<option value="{{ $option }}" @selected($field['value'] === $option)>{{ $option === '' ? 'None' : strtoupper($option) }}</option>@endforeach
         </select>
        @elseif($field['type'] === 'toggle')
         <select name="{{ str_replace('.', '__', $key) }}" class="{{ $input }}">
          <option value="1" @selected($field['value'] === '' || in_array(strtolower($field['value']), ['1','true','yes','on'], true))>Enabled</option>
          <option value="0" @selected($field['value'] !== '' && !in_array(strtolower($field['value']), ['1','true','yes','on'], true))>Disabled</option>
         </select>
        @elseif($field['type'] === 'decimal')
         <input type="number" step="0.01" min="{{ $field['min'] ?? 0 }}" max="{{ $field['max'] ?? '' }}" name="{{ str_replace('.', '__', $key) }}" value="{{ $field['value'] }}" class="{{ $input }}">
        @else
         <input type="{{ $field['type'] }}" name="{{ str_replace('.', '__', $key) }}" value="{{ $field['value'] }}"
          placeholder="{{ ($field['encrypted'] ?? false) ? (($field['configured'] ?? false) ? '•••••••• (configured — enter to replace)' : 'Not configured') : '' }}"
          @if($field['type'] === 'password') autocomplete="new-password" @endif class="{{ $input }}">
        @endif
       </div>
      @endforeach
     </div>
     <button class="mt-6 rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Save {{ $section['title'] }}</button>
    </form>
   </div>
  @endforeach

  <div data-tab-panel="advanced" class="hidden rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">data_object</span>All Settings</h2>
   <p class="mt-1 text-sm text-[#626576]">Raw key/value store behind the sections above.</p>
   <form method="GET" class="mt-4 flex max-w-xl gap-2"><input type="hidden" name="tab" value="advanced"><label class="relative flex-1"><span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#777a8a]">search</span><input name="q" value="{{ request('q') }}" placeholder="Search setting key or group" class="w-full rounded-lg border border-[#d7d9e5] bg-white py-2.5 pl-10 pr-4 text-sm"></label><button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white">Search</button>@if(request('q'))<a href="{{ route('admin.settings.index', ['tab' => 'advanced']) }}" class="self-center text-sm font-semibold text-[#3525cd]">Clear</a>@endif</form>
   <div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="space-y-6">
     @forelse($settings as $group=>$items)
     <div class="rounded-xl border border-[#d7d9e5] bg-[#fafbff] p-6">
      <h3 class="flex items-center gap-2 text-lg font-bold capitalize"><span class="material-symbols-outlined text-[#3525cd]">folder</span>{{ $group }}</h3>
      <div class="mt-4 space-y-3">
      @foreach($items as $setting)
       <form method="POST" action="{{ route('admin.settings.update') }}" class="flex flex-wrap items-center gap-2">@csrf @method('PUT')
        <input type="hidden" name="group" value="{{ $setting->group }}"><input type="hidden" name="key" value="{{ $setting->key }}">
        <span class="w-56 font-mono text-xs text-[#555868]">{{ $setting->key }}</span>
        <input name="value" value="{{ $setting->is_encrypted?'':$setting->value }}" @if($setting->is_encrypted) type="password" placeholder="Encrypted" @endif class="min-w-0 flex-1 rounded-lg border border-[#d7d9e5] bg-white px-3 py-2 text-sm outline-none focus:border-[#3525cd]">
        <button class="rounded-lg border border-emerald-300 px-3 py-1.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">Save</button>
       </form>
      @endforeach
      </div>
     </div>
     @empty<div class="rounded-xl border border-dashed border-[#d7d9e5] bg-[#fafbff] p-16 text-center text-[#777a8a]">No settings stored yet.</div>@endforelse
    </div>
    <div class="self-start rounded-xl border border-[#d7d9e5] bg-[#fafbff] p-6">
     <h3 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">add_circle</span>Add setting</h3>
     <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 space-y-3">@csrf @method('PUT')
      <input name="group" required placeholder="Group, e.g. marketplace" class="{{ $input }}">
      <input name="key" required placeholder="Key, e.g. marketplace.support_email" class="{{ $input }}">
      <input name="value" placeholder="Value" class="{{ $input }}">
      <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Save setting</button>
     </form>
    </div>
   </div>
  </div>
  </div>
 </div>
</div>
</x-admin-layout>
