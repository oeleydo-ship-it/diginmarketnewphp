<x-marketplace-layout :title="$category->name . ' — DiginMarket'" :description="$category->description">
<div class="mx-auto max-w-7xl px-6 py-12">
    <header class="mb-10 flex items-start gap-5">
        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary-container/30 text-primary">
            <span class="material-symbols-outlined text-[34px]">{{ $category->icon ?: 'category' }}</span>
        </span>
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Category</p>
            <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">{{ $category->name }}</h1>
            <p class="mt-3 max-w-2xl text-on-surface-variant">{{ $category->description ?? 'Explore reviewed products from independent sellers.' }}</p>
        </div>
    </header>
    @php($field = 'rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20')
    @php($hasFilters = collect(['q','min_price','max_price','min_rating','business'])->contains(fn ($k) => request()->filled($k)) || request('sort'))
    <form method="GET" action="{{ route('categories.show', $category->slug) }}" class="mb-4 grid gap-3 rounded-xl border border-outline-variant bg-surface-container-low p-5 md:grid-cols-6">
        <div class="relative md:col-span-2">
            <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-on-surface-variant">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="Search in {{ $category->name }}" class="{{ $field }} w-full pl-10">
        </div>
        <input name="min_price" type="number" min="0" step="0.01" value="{{ request('min_price') }}" placeholder="Min price" class="{{ $field }}">
        <input name="max_price" type="number" min="0" step="0.01" value="{{ request('max_price') }}" placeholder="Max price" class="{{ $field }}">
        <select name="min_rating" class="{{ $field }}">
            <option value="">Any rating</option>
            @foreach([4 => '4★ & up', 3 => '3★ & up', 2 => '2★ & up'] as $val => $lbl)
                <option value="{{ $val }}" @selected((string) request('min_rating') === (string) $val)>{{ $lbl }}</option>
            @endforeach
        </select>
        <select name="sort" class="{{ $field }}">
            @foreach(['newest' => 'Newest', 'popular' => 'Best selling', 'rated' => 'Highest rated', 'price_low' => 'Lowest price', 'price_high' => 'Highest price', 'title' => 'Name (A–Z)'] as $val => $lbl)
                <option value="{{ $val }}" @selected(request('sort') === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-on-surface-variant md:col-span-2">
            <input type="checkbox" name="business" value="1" @checked(request('business') === '1') class="rounded border-outline-variant text-primary focus:ring-primary/20">
            Business license available
        </label>
        <div class="flex gap-2 md:col-span-4">
            <button class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-primary p-3 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
                <span class="material-symbols-outlined text-[18px]">tune</span> Apply filters
            </button>
            @if($hasFilters)
                <a href="{{ route('categories.show', $category->slug) }}" class="flex items-center justify-center gap-2 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-colors hover:border-primary hover:text-primary">Clear</a>
            @endif
        </div>
    </form>
    <p class="mb-6 text-sm text-on-surface-variant">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}{{ $hasFilters ? ' match your filters' : ' in this category' }}.</p>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">{{ $hasFilters ? 'search_off' : 'category' }}</span>
                <p class="text-on-surface-variant">{{ $hasFilters ? 'No products match these filters.' : 'No published products in this category.' }}</p>
            </div>
        @endforelse
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
