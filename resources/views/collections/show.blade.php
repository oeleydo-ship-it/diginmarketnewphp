<x-marketplace-layout :title="$collection->title.' — Collection'">
<div class="mx-auto max-w-7xl px-6 py-12">
    <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Collection @unless($collection->is_public)· Private @endunless</p>
    <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">{{ $collection->title }}</h1>
    <p class="mt-2 text-sm text-on-surface-variant">Curated by {{ $collection->user->name }} · {{ $collection->products_count }} products</p>
    @if($collection->description)<p class="mt-3 max-w-2xl text-on-surface-variant">{{ $collection->description }}</p>@endif
    @if(session('status'))<div class="mt-6 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container">{{ session('status') }}</div>@endif

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <div class="relative">
                <x-product-card :product="$product" />
                @if($collection->user_id === auth()->id())
                    <form method="POST" action="{{ route('collections.remove', [$collection, $product]) }}" class="absolute right-3 top-3">@csrf @method('DELETE')
                        <button aria-label="Remove from collection" class="flex h-8 w-8 items-center justify-center rounded-full bg-surface/90 text-on-surface-variant shadow transition-colors hover:text-error"><span class="material-symbols-outlined text-[18px]">close</span></button>
                    </form>
                @endif
            </div>
        @empty
            <p class="col-span-full rounded-xl border border-outline-variant bg-surface-container-lowest p-10 text-center text-sm text-on-surface-variant">This collection is empty.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
