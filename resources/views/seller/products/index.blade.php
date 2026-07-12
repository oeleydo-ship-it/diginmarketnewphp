<x-marketplace-layout title="My Products — DiginMarket">
<div class="mx-auto max-w-7xl px-6 py-12">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Catalog</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">My Products</h1>
        </div>
        <a href="{{ route('seller.products.create') }}" class="flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Add product
        </a>
    </div>
    <div class="space-y-3">
        @forelse($products as $product)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-container/20 text-primary">
                        <span class="material-symbols-outlined">inventory_2</span>
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold">{{ $product->title }}</h2>
                        @php($status = $product->status->value)
                        <span class="mt-1 inline-block rounded px-2 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider {{ ['approved' => 'bg-secondary-container/40 text-on-secondary-container', 'published' => 'bg-secondary-container/40 text-on-secondary-container', 'rejected' => 'bg-error-container text-on-error-container', 'changes_requested' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant', 'submitted' => 'bg-primary-container/20 text-primary'][$status] ?? 'bg-outline-variant/30 text-on-surface-variant' }}">{{ str($status)->headline() }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('seller.products.edit', $product) }}" class="flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-variant transition-all hover:border-primary hover:text-primary active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    Manage
                </a>
                @can('submit', $product)
                    <form method="POST" action="{{ route('seller.products.submit', $product) }}">
                        @csrf
                        <button class="flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition-all hover:bg-primary hover:text-on-primary active:scale-95">
                            <span class="material-symbols-outlined text-[18px]">send</span>
                            Submit for review
                        </button>
                    </form>
                @endcan
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">inventory_2</span>
                <p class="text-on-surface-variant">No products yet.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
