<x-marketplace-layout title="Settings">
<div class="mx-auto max-w-7xl px-6 py-12">
 <h1 class="text-4xl font-black">Settings</h1>
 <div class="mt-6">@include('admin.partials.nav')</div>
 @if($errors->any())<p class="mt-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-rose-300">{{ $errors->first() }}</p>@endif
 <div class="mt-8 grid gap-8 lg:grid-cols-2">
  <div class="space-y-6">
   @forelse($settings as $group=>$items)
   <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
    <h2 class="text-xl font-bold capitalize">{{ $group }}</h2>
    <div class="mt-4 space-y-3">
    @foreach($items as $setting)
     <form method="POST" action="{{ route('admin.settings.update') }}" class="flex flex-wrap items-center gap-2">@csrf @method('PUT')
      <input type="hidden" name="group" value="{{ $setting->group }}"><input type="hidden" name="key" value="{{ $setting->key }}">
      <span class="w-56 text-sm text-slate-300">{{ $setting->key }}</span>
      <input name="value" value="{{ $setting->is_encrypted?'':$setting->value }}" @if($setting->is_encrypted) type="password" placeholder="Encrypted" @endif class="flex-1 rounded-lg bg-white/5 p-2">
      <button class="rounded-lg border border-emerald-400 px-3 py-1.5 font-semibold text-emerald-300">Save</button>
     </form>
    @endforeach
    </div>
   </div>
   @empty<p class="text-slate-400">No settings stored yet.</p>@endforelse
  </div>
  <div class="rounded-2xl border border-white/10 bg-white/5 p-6 self-start">
   <h2 class="text-xl font-bold">Add setting</h2>
   <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 space-y-3">@csrf @method('PUT')
    <input name="group" required placeholder="Group, e.g. marketplace" class="w-full rounded-lg bg-white/5 p-2">
    <input name="key" required placeholder="Key, e.g. marketplace.support_email" class="w-full rounded-lg bg-white/5 p-2">
    <input name="value" placeholder="Value" class="w-full rounded-lg bg-white/5 p-2">
    <button class="rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950">Save setting</button>
   </form>
  </div>
 </div>
</div>
</x-marketplace-layout>
