<x-marketplace-layout title="My Collections — DiginMarket">
<div class="mx-auto max-w-5xl px-6 py-12">
    <h1 class="font-display text-3xl font-semibold tracking-tight">My collections</h1>
    <p class="mt-2 text-sm text-on-surface-variant">Curate public lists of products you recommend, or keep private shortlists for later.</p>
    @if(session('status'))<div class="mt-6 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container">{{ session('status') }}</div>@endif

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse($collections as $collection)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
                    <div>
                        <a href="{{ route('collections.show', $collection->slug) }}" class="font-display font-bold hover:text-primary">{{ $collection->title }}</a>
                        <p class="text-xs text-on-surface-variant">{{ $collection->products_count }} products · {{ $collection->is_public ? 'Public' : 'Private' }}</p>
                    </div>
                    <form method="POST" action="{{ route('collections.destroy', $collection) }}">@csrf @method('DELETE')
                        <button class="text-sm font-semibold text-on-surface-variant hover:text-error">Delete</button>
                    </form>
                </div>
            @empty
                <p class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center text-sm text-on-surface-variant">No collections yet — create one alongside, or save any product from its page.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('collections.store') }}" class="space-y-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-6">@csrf
            <h2 class="font-display font-bold">New collection</h2>
            <input name="title" required maxlength="120" placeholder="Title" class="w-full rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
            <textarea name="description" rows="2" placeholder="Description (optional)" class="w-full rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
            <label class="flex items-center gap-2 text-sm text-on-surface-variant"><input type="checkbox" name="is_public" value="1" checked class="rounded text-primary focus:ring-primary">Public — anyone with the link can view</label>
            <button class="w-full rounded-xl bg-primary p-3 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Create</button>
        </form>
    </div>
</div>
</x-marketplace-layout>
