<x-marketplace-layout :title="($post->meta_title ?: $post->title).' — DiginMarket Blog'" :description="$post->meta_description ?: $post->excerpt">
<div class="mx-auto max-w-3xl px-6 py-16">
 <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-primary">← All articles</a>
 <p class="mt-6 font-mono text-xs uppercase tracking-widest text-on-surface-variant">{{ $post->published_at->format('F j, Y') }}@if($post->author) · {{ $post->author->name }}@endif</p>
 <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight">{{ $post->title }}</h1>
 @if($post->excerpt)<p class="mt-4 text-lg text-on-surface-variant">{{ $post->excerpt }}</p>@endif
 <div class="mt-8 max-w-none whitespace-pre-line text-[15px] leading-relaxed text-on-surface">{{ $post->body }}</div>
 @if($more->isNotEmpty())
 <div class="mt-14 border-t border-outline-variant pt-8">
  <h2 class="font-display text-xl font-semibold">More from the blog</h2>
  <div class="mt-4 grid gap-4 sm:grid-cols-3">
   @foreach($more as $other)
   <a href="{{ route('blog.show',$other->slug) }}" class="rounded-xl border border-outline-variant p-4 text-sm font-semibold leading-snug transition hover:border-primary hover:text-primary">{{ $other->title }}</a>
   @endforeach
  </div>
 </div>
 @endif
</div>
</x-marketplace-layout>
