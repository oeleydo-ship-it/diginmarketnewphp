<x-marketplace-layout :title="$page->meta_title ?: $page->title" :description="$page->meta_description ?: $page->excerpt">
<div class="mx-auto max-w-3xl px-6 py-16">
 <h1 class="text-4xl font-black">{{ $page->title }}</h1>
 @if($page->excerpt)<p class="mt-4 text-lg text-slate-400">{{ $page->excerpt }}</p>@endif
 <div class="prose prose-invert mt-8 max-w-none whitespace-pre-line text-slate-300">{{ $page->body }}</div>
 <p class="mt-10 text-sm text-slate-500">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
</div>
</x-marketplace-layout>
