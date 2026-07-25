<x-marketplace-layout title="Downloads — DiginMarket">
<x-customer-panel title="Downloads" subtitle="Your files and update history">
    <header class="mb-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">Downloads</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Every product you own, every version you are entitled to, and a log of what you have already fetched. Updates are free for the lifetime of the licence.</p>
    </header>

    <section class="space-y-5">
        @forelse($library as $entry)
            @php($license = $entry['license'])
            <article class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-primary">{{ $license->product?->title ?? 'Removed product' }}</h2>
                        <p class="mt-1 font-mono text-[11px] uppercase tracking-wider text-on-surface-variant">
                            {{ $license->license_key }}
                            @if($license->version) · bought at v{{ $license->version->version_number }}@endif
                        </p>
                    </div>
                    @if($entry['has_update'])
                        <span class="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-on-tertiary-fixed-variant">
                            <span class="material-symbols-outlined text-[14px]">upgrade</span> Update available
                        </span>
                    @elseif($license->status !== 'active')
                        <span class="inline-flex items-center gap-1 rounded-full bg-error-container px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-on-error-container">
                            <span class="material-symbols-outlined text-[14px]">block</span> {{ $license->status }}
                        </span>
                    @endif
                </div>
                @if($license->status === 'active' && count($entry['versions']))
                    <ul class="divide-y divide-outline-variant border-t border-outline-variant">
                        @foreach($entry['versions'] as $row)
                            <li class="flex flex-wrap items-center justify-between gap-3 bg-surface px-6 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-on-surface">
                                        v{{ $row['version']->version_number }}
                                        @if($row['version']->release_title)<span class="font-normal text-on-surface-variant">— {{ $row['version']->release_title }}</span>@endif
                                        @if($row['is_purchased'])<span class="ml-1 rounded bg-surface-container px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-wider text-on-surface-variant">purchased</span>@endif
                                        @if($loop->first && !$row['is_purchased'])<span class="ml-1 rounded bg-secondary-container/40 px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-wider text-on-secondary-container">latest</span>@endif
                                    </p>
                                    @if($row['version']->published_at)
                                        <p class="mt-0.5 font-mono text-[10px] uppercase tracking-wider text-on-surface-variant">Released {{ $row['version']->published_at->toFormattedDateString() }}</p>
                                    @endif
                                </div>
                                <a href="{{ $row['url'] }}" class="flex items-center gap-1.5 rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-on-surface transition-colors hover:border-primary hover:text-primary">
                                    <span class="material-symbols-outlined text-[16px]">download</span> Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="border-t border-outline-variant bg-surface px-6 py-4 text-sm text-on-surface-variant">Downloads are disabled for this licence.</p>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-6 py-16 text-center">
                <span class="material-symbols-outlined mb-3 block text-5xl text-on-surface-variant">download</span>
                <h2 class="font-semibold text-on-surface">Nothing to download yet</h2>
                <p class="mt-1 text-sm text-on-surface-variant">Products you buy appear here with every version you are entitled to.</p>
                <a href="{{ route('products.index') }}" class="mt-5 inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary">Browse the marketplace</a>
            </div>
        @endforelse
    </section>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold tracking-tight">Download history</h2>
        <div class="mt-4 overflow-x-auto rounded-xl border border-outline-variant bg-surface-container-lowest">
            <table class="w-full min-w-[520px] text-left text-sm">
                <thead class="border-b border-outline-variant text-[11px] uppercase tracking-wider text-on-surface-variant">
                    <tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Version</th><th class="px-5 py-3">When</th><th class="px-5 py-3">IP</th></tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($downloads as $download)
                        <tr>
                            <td class="px-5 py-3 font-medium text-on-surface">{{ $download->product?->title ?? '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-on-surface-variant">{{ $download->version?->version_number ?? '—' }}</td>
                            <td class="px-5 py-3 text-on-surface-variant">{{ $download->downloaded_at?->format('M j, Y H:i') }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-on-surface-variant">{{ $download->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-on-surface-variant">No downloads recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $downloads->links() }}</div>
    </section>
</x-customer-panel>
</x-marketplace-layout>
