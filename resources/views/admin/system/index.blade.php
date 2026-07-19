<x-admin-layout title="System Health">
<div class="mx-auto max-w-[1400px] px-5 py-8 md:px-8 lg:py-10">
 <div><h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">System Health</h1><p class="mt-2 text-[#626576]">Runtime, queue, webhook, and storage visibility.</p></div>
 @php($fmt=fn($bytes)=>$bytes>=1073741824?number_format($bytes/1073741824,2).' GB':($bytes>=1048576?number_format($bytes/1048576,1).' MB':number_format($bytes/1024,0).' KB'))
 <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm"><p class="text-sm text-[#626576]">Pending jobs</p><p class="mt-1 text-2xl font-extrabold {{ $health['pending_jobs']>50?'text-amber-600':'' }}">{{ number_format($health['pending_jobs']) }}</p><p class="mt-1 text-xs text-[#777a8a]">queue: {{ $health['queue_driver'] }}</p></div>
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm"><p class="text-sm text-[#626576]">Failed jobs</p><p class="mt-1 text-2xl font-extrabold {{ $health['failed_jobs']?'text-red-600':'text-emerald-600' }}">{{ number_format($health['failed_jobs']) }}</p><p class="mt-1 text-xs text-[#777a8a]">retry with php artisan queue:retry all</p></div>
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm"><p class="text-sm text-[#626576]">Stripe webhooks received</p><p class="mt-1 text-2xl font-extrabold">{{ number_format($health['webhook_events']) }}</p><p class="mt-1 text-xs text-[#777a8a]">last: {{ $health['last_webhook_at'] ?? 'never' }}</p></div>
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-5 shadow-sm"><p class="text-sm text-[#626576]">Last earnings clearance</p><p class="mt-1 text-2xl font-extrabold">{{ $health['last_clearance_at'] ? \Illuminate\Support\Carbon::parse($health['last_clearance_at'])->diffForHumans() : 'never' }}</p><p class="mt-1 text-xs text-[#777a8a]">scheduled daily at 01:00</p>
   <form method="POST" action="{{ route('admin.system.clear-earnings') }}" class="mt-3">@csrf<button class="rounded-lg border border-[#3525cd] px-4 py-2 text-xs font-semibold text-[#3525cd] transition hover:bg-[#3525cd] hover:text-white">Run clearance now</button></form>
  </div>
 </div>
 <div class="mt-6 grid gap-6 lg:grid-cols-2">
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">dns</span>Runtime</h2>
   <dl class="mt-4 space-y-2 text-sm">
    <div class="flex justify-between border-b border-[#eef0f6] pb-2"><dt class="text-[#626576]">Environment</dt><dd class="font-semibold {{ $health['environment']==='production'&&$health['debug']?'text-red-600':'' }}">{{ $health['environment'] }} @if($health['debug'])(debug on)@endif</dd></div>
    <div class="flex justify-between border-b border-[#eef0f6] pb-2"><dt class="text-[#626576]">PHP</dt><dd class="font-mono">{{ $health['php_version'] }}</dd></div>
    <div class="flex justify-between border-b border-[#eef0f6] pb-2"><dt class="text-[#626576]">Laravel</dt><dd class="font-mono">{{ $health['laravel_version'] }}</dd></div>
    <div class="flex justify-between border-b border-[#eef0f6] pb-2"><dt class="text-[#626576]">Cache driver</dt><dd class="font-mono">{{ $health['cache_driver'] }}</dd></div>
    <div class="flex justify-between"><dt class="text-[#626576]">Mail transport</dt><dd class="font-mono {{ $health['mail_mailer']==='log'?'text-amber-600':'' }}">{{ $health['mail_mailer'] }}</dd></div>
   </dl>
  </div>
  <div class="rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">hard_drive</span>Storage</h2>
   <dl class="mt-4 space-y-2 text-sm">
    <div class="flex justify-between border-b border-[#eef0f6] pb-2"><dt class="text-[#626576]">Product files</dt><dd class="font-semibold">{{ number_format($health['product_files']) }}</dd></div>
    <div class="flex justify-between border-b border-[#eef0f6] pb-2"><dt class="text-[#626576]">Product storage used</dt><dd class="font-semibold">{{ $fmt($health['product_storage_bytes']) }}</dd></div>
    <div class="flex justify-between"><dt class="text-[#626576]">Disk free</dt><dd class="font-semibold">{{ $fmt($health['storage_free_bytes']) }}</dd></div>
   </dl>
   <p class="mt-4 rounded-lg bg-[#f6f7fb] p-3 text-xs leading-5 text-[#626576]">Back up the database together with <span class="font-mono">storage/app</span> — licenses and orders are only meaningful alongside the files they unlock.</p>
  </div>
 </div>
 <div class="mt-6 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-3">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">system_update_alt</span>Application update</h2>
   @php($lastUpdate = \App\Support\AppVersion::lastUpdate())
   <p class="mt-2 text-sm text-[#626576]">
    Current version: <strong class="font-mono">v{{ \App\Support\AppVersion::current() }}</strong>
    @if($lastUpdate['at'])
     · last updated {{ \Illuminate\Support\Carbon::parse($lastUpdate['at'])->diffForHumans() }}@if($lastUpdate['previous']) (from v{{ $lastUpdate['previous'] }})@endif
    @endif
    <br>Upload a release zip with an <code>update-manifest.json</code> (<code>{"version": "x.y.z"}</code>) — only newer versions apply, a backup is taken, the site enters maintenance mode while files are replaced, migrations run, and caches are cleared. Your .env, storage and uploads are never touched.
   </p>
   @error('package')<p class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
   <form method="POST" action="{{ route('admin.system.update') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-3" data-confirm="Apply this update now? The site will be briefly unavailable.">@csrf
    <input type="file" name="package" accept=".zip" required class="text-sm">
    <label class="flex items-center gap-1.5 text-xs text-[#626576]"><input type="checkbox" name="allow_downgrade" value="1">Allow same or older version (reinstall / rollback)</label>
    <button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Apply update</button>
   </form>
  </section>

  <section class="mt-8 rounded-2xl border border-[#cfd2e1] bg-white p-6 shadow-sm">
   <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">backup</span>Backups</h2>
   <form method="POST" action="{{ route('admin.system.backup') }}">@csrf<button class="rounded-lg bg-[#3525cd] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">Run backup now</button></form>
  </div>
  @if(session('status'))<p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">{{ session('status') }}</p>@endif
  <div class="mt-4 divide-y divide-[#eef0f6]">
  @forelse($backups as $backup)
   <div class="flex flex-wrap items-center justify-between gap-2 py-3"><p class="font-mono text-sm">{{ $backup['name'] }}</p><p class="text-sm text-[#626576]">{{ $fmt($backup['size']) }} · {{ $backup['created_at']->diffForHumans() }}</p></div>
  @empty<p class="py-6 text-center text-sm text-[#777a8a]">No backups yet. Backups run daily at 02:30 and are kept for {{ config('marketplace.backup_keep',7) }} generations.</p>@endforelse
  </div>
 </div>
 <div class="mt-6 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  <h2 class="flex items-center gap-2 text-lg font-bold"><span class="material-symbols-outlined text-[#3525cd]">report</span>Recent failed jobs</h2>
  <div class="mt-4 divide-y divide-[#eef0f6]">
  @forelse($failedJobs as $job)
   <div class="py-3"><p class="font-mono text-xs text-[#555868]">{{ $job->uuid }} · {{ $job->queue }} · {{ $job->failed_at }}</p><p class="mt-1 truncate text-sm text-red-700">{{ str(json_decode($job->payload)->displayName ?? 'Unknown job') }}</p></div>
  @empty<p class="py-6 text-center text-sm text-[#777a8a]">No failed jobs. 🎉</p>@endforelse
  </div>
 </div>
</div>
</x-admin-layout>
