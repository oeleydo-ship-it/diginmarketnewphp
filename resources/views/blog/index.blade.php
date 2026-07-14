<x-marketplace-layout title="Blog — DiginMarket" description="Product updates, seller guides, and marketplace news from the DiginMarket team.">
<div class="mx-auto max-w-7xl px-6 py-16">
 <p class="font-mono text-xs uppercase tracking-widest text-primary">From the team</p>
 <h1 class="mt-2 font-display text-4xl font-semibold tracking-tight">DiginMarket Blog</h1>
 <p class="mt-3 max-w-2xl text-on-surface-variant">Product updates, seller guides, and marketplace news.</p>
 <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
 @forelse($posts as $post)
  <a href="{{ route('blog.show',$post->slug) }}" class="group flex flex-col rounded-xl border border-outline-variant bg-surface-container p-6 transition hover:border-primary">
   <p class="font-mono text-xs uppercase tracking-wider text-on-surface-variant">{{ $post->published_at->format('M j, Y') }}</p>
   <h2 class="mt-3 font-display text-xl font-semibold leading-snug group-hover:text-primary">{{ $post->title }}</h2>
   @if($post->excerpt)<p class="mt-3 text-sm leading-6 text-on-surface-variant">{{ $post->excerpt }}</p>@endif
   <span class="mt-auto pt-5 text-sm font-semibold text-primary">Read article →</span>
  </a>
 @empty
  <p class="col-span-full rounded-xl border border-dashed border-outline-variant p-16 text-center text-on-surface-variant">No articles published yet — check back soon.</p>
 @endforelse
 </div>
 <div class="mt-8">{{ $posts->links() }}</div>
</div>
</x-marketplace-layout>
