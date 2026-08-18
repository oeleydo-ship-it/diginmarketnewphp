<x-marketplace-layout
    :title="$page->meta_title ?: (\App\Models\Setting::get('seo.meta_title') ?: $page->title)"
    :description="$page->meta_description ?: (\App\Models\Setting::get('seo.meta_description') ?: $page->excerpt)"
>
<div class="mx-auto max-w-3xl px-6 py-16">
    <h1 class="font-display text-4xl font-semibold tracking-tight">{{ $page->title }}</h1>
    @if($page->excerpt)<p class="mt-4 text-lg text-on-surface-variant">{{ $page->excerpt }}</p>@endif
    <x-rich-content :html="$page->body" class="mt-8 max-w-none text-[15px] leading-relaxed text-on-surface" />
    <p class="mt-10 border-t border-outline-variant pt-6 font-mono text-xs uppercase tracking-wider text-on-surface-variant">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
</div>
</x-marketplace-layout>
