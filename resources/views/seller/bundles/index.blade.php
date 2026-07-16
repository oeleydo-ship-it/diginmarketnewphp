<x-marketplace-layout title="Bundles — Seller Studio">
<div class="mx-auto max-w-7xl px-6 py-10">
    <div class="mb-6">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Studio</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Bundles</h1>
        <p class="mt-2 text-sm text-on-surface-variant">Sell two or more of your published products together at one price. Buyers get a normal license for each product.</p>
    </div>
    <x-seller-nav />

    @if(session('status'))<div class="mb-6 flex items-center gap-2 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container"><span class="material-symbols-outlined text-[20px]">check_circle</span>{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-6 rounded-xl border border-error/30 bg-error-container/40 px-4 py-3 text-sm font-medium text-on-error-container">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    @php($input = 'w-full rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20')

    <div class="grid items-start gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
            <h2 class="font-display text-lg font-bold">Your bundles</h2>
            @forelse($bundles as $bundle)
                <div class="mt-4 rounded-xl border border-outline-variant/60 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $bundle->title }} @unless($bundle->is_active)<span class="ml-1 rounded bg-surface-container px-2 py-0.5 text-xs text-on-surface-variant">Inactive</span>@endunless</p>
                            <p class="text-xs text-on-surface-variant">${{ number_format((float) $bundle->price, 2) }} · {{ $bundle->products_count }} products</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('bundles.show', $bundle->slug) }}" class="text-sm font-semibold text-primary hover:underline">View</a>
                            <form method="POST" action="{{ route('seller.bundles.destroy', $bundle) }}" onsubmit="return confirm('Delete this bundle?')">@csrf @method('DELETE')
                                <button class="text-sm font-semibold text-on-surface-variant hover:text-error">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="mt-4 text-sm text-on-surface-variant">No bundles yet. Create one alongside — it goes live immediately.</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
            <h2 class="font-display text-lg font-bold">Create a bundle</h2>
            @if($products->count() < 2)
                <p class="mt-4 text-sm text-on-surface-variant">You need at least two published products to create a bundle.</p>
            @else
                <form method="POST" action="{{ route('seller.bundles.store') }}" class="mt-4 space-y-4">@csrf
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Title</label><input name="title" required maxlength="150" class="{{ $input }}"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Description</label><textarea name="description" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Bundle price</label><input name="price" type="number" step="0.01" min="1" required class="{{ $input }}"></div>
                    <fieldset>
                        <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Products (pick at least 2)</legend>
                        <div class="max-h-56 space-y-2 overflow-y-auto rounded-lg border border-outline-variant p-3">
                            @foreach($products as $product)
                                <label class="flex items-center justify-between gap-2 text-sm">
                                    <span class="flex items-center gap-2"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="rounded text-primary focus:ring-primary">{{ $product->title }}</span>
                                    <span class="text-on-surface-variant">${{ number_format((float) $product->regular_price, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <button class="w-full rounded-xl bg-primary p-3 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Create bundle</button>
                </form>
            @endif
        </section>
    </div>
</div>
</x-marketplace-layout>
