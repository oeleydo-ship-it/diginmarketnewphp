<x-marketplace-layout :title="$category->name . ' — DiginMarket'" :description="$category->description">
@php
    $view = request('view') === 'grid' ? 'grid' : 'list';
    $hasFilters = collect(['q','min_price','max_price','min_rating','business'])->contains(fn ($k) => request()->filled($k)) || request('sort');
    $field = 'w-full rounded-lg border border-outline-variant bg-surface p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20';
    $sortLabels = ['newest' => 'Newest', 'popular' => 'Best selling', 'rated' => 'Highest rated', 'price_low' => 'Lowest price', 'price_high' => 'Highest price', 'title' => 'Name (A–Z)'];
@endphp
<div class="mx-auto max-w-[1400px] px-4 py-8 md:px-6 lg:py-10">
    <header class="mb-8 flex items-start gap-5">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary-container/30 text-primary">
            <span class="material-symbols-outlined text-[30px]">{{ $category->displayIcon() }}</span>
        </span>
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Category</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">{{ $category->name }}</h1>
            <p class="mt-2 max-w-2xl text-on-surface-variant">{{ $category->description ?? 'Explore reviewed products from independent sellers.' }}</p>
        </div>
    </header>

    <form method="GET" action="{{ route('categories.show', $category->slug) }}" class="flex flex-col gap-6 lg:flex-row lg:items-start">
        <input type="hidden" name="view" value="{{ $view }}">

        <!-- Sidebar -->
        <aside class="w-full shrink-0 lg:sticky lg:top-6 lg:w-64">
            <details class="group rounded-xl border border-outline-variant bg-surface-container-lowest" open>
                <summary class="flex cursor-pointer items-center justify-between p-4 font-semibold lg:cursor-default">
                    <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-primary">tune</span>Filters</span>
                    @if($hasFilters)<a href="{{ route('categories.show', [$category->slug, 'view' => $view]) }}" class="text-xs font-semibold text-primary hover:underline">Clear all</a>@endif
                </summary>
                <div class="space-y-6 border-t border-outline-variant p-4">
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Search</label>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant">search</span>
                            <input name="q" value="{{ request('q') }}" placeholder="Keyword" class="{{ $field }} pl-9">
                        </div>
                    </div>
                    <div>
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Categories</span>
                        <ul class="space-y-1 text-sm">
                            @foreach($categories as $cat)
                                <li>
                                    <a href="{{ route('categories.show', [$cat->slug, 'view' => $view]) }}"
                                        class="flex items-center justify-between rounded-lg px-2.5 py-1.5 transition-colors {{ $cat->id === $category->id ? 'bg-primary/10 font-semibold text-primary' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">
                                        <span class="truncate">{{ $cat->name }}</span>
                                        <span class="ml-2 shrink-0 font-mono text-xs opacity-70">{{ $cat->products_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Price range</label>
                        <div class="flex items-center gap-2">
                            <input name="min_price" type="number" min="0" step="0.01" value="{{ request('min_price') }}" placeholder="Min" class="{{ $field }}">
                            <span class="text-on-surface-variant">–</span>
                            <input name="max_price" type="number" min="0" step="0.01" value="{{ request('max_price') }}" placeholder="Max" class="{{ $field }}">
                        </div>
                    </div>
                    <div>
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Rating</span>
                        <div class="space-y-1.5">
                            @foreach(['' => 'Any rating', '4' => '4★ & up', '3' => '3★ & up', '2' => '2★ & up'] as $val => $lbl)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface-variant">
                                    <input type="radio" name="min_rating" value="{{ $val }}" @checked((string) request('min_rating') === (string) $val) class="text-primary focus:ring-primary/20">
                                    {{ $lbl }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">License</span>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface-variant">
                            <input type="checkbox" name="business" value="1" @checked(request('business') === '1') class="rounded border-outline-variant text-primary focus:ring-primary/20">
                            Business license available
                        </label>
                    </div>
                    <button class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary p-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply filters
                    </button>
                </div>
            </details>
        </aside>

        <!-- Results -->
        <div class="min-w-0 flex-1">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3">
                <p class="text-sm text-on-surface-variant">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}{{ $hasFilters ? ' match your filters' : '' }}</p>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                        <span class="hidden sm:inline">Sort</span>
                        <select name="sort" data-submit-on-change class="rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                            @foreach($sortLabels as $val => $lbl)
                                <option value="{{ $val }}" @selected(request('sort', 'newest') === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex items-center gap-1 rounded-lg border border-outline-variant bg-surface p-1">
                        <a href="{{ route('categories.show', [$category->slug, ...request()->except(['view', 'page']), 'view' => 'grid']) }}" aria-label="Grid view" title="Grid view"
                            class="flex h-8 w-8 items-center justify-center rounded-md transition-colors {{ $view === 'grid' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:text-primary' }}">
                            <span class="material-symbols-outlined text-[20px]">grid_view</span>
                        </a>
                        <a href="{{ route('categories.show', [$category->slug, ...request()->except(['view', 'page']), 'view' => 'list']) }}" aria-label="List view" title="List view"
                            class="flex h-8 w-8 items-center justify-center rounded-md transition-colors {{ $view === 'list' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:text-primary' }}">
                            <span class="material-symbols-outlined text-[20px]">view_list</span>
                        </a>
                    </div>
                </div>
            </div>

            @if($products->isEmpty())
                <div class="rounded-xl border border-dashed border-outline-variant p-14 text-center">
                    <span class="material-symbols-outlined mb-3 text-[40px] text-outline">{{ $hasFilters ? 'search_off' : 'category' }}</span>
                    <p class="text-on-surface-variant">{{ $hasFilters ? 'No products match these filters.' : 'No published products in this category.' }}</p>
                </div>
            @elseif($view === 'list')
                <div class="flex flex-col gap-4">
                    @foreach($products as $product)
                        <x-product-row :product="$product" />
                    @endforeach
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            @endif
            <div class="mt-10">{{ $products->links() }}</div>
        </div>
    </form>
</div>
</x-marketplace-layout>
