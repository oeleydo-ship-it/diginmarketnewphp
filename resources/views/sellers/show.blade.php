<x-marketplace-layout :title="$seller->display_name . ' — DiginMarket'" :description="$seller->biography">
<div class="border-b border-outline-variant bg-surface-container-low">
    <div class="mx-auto max-w-7xl px-6 py-14">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <div class="flex items-start gap-5">
                <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-primary-container font-display text-3xl font-bold text-on-primary-container">{{ str($seller->display_name)->substr(0, 1)->upper() }}</span>
                <div>
                    <p class="flex items-center gap-1 font-mono text-xs font-medium uppercase tracking-wider text-secondary">
                        <span class="material-symbols-outlined text-[16px]">verified</span> Verified Seller
                    </p>
                    <h1 class="mt-1 font-display text-4xl font-semibold tracking-tight">{{ $seller->display_name }}</h1>
                    @if($seller->business_name)
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-medium text-on-surface-variant">
                            <span class="material-symbols-outlined text-[16px] text-secondary">workspace_premium</span>
                            {{ $seller->business_name }} · Verified business
                        </p>
                    @endif
                    <p class="mt-3 max-w-2xl text-on-surface-variant">{{ $seller->biography }}</p>
                    <p class="mt-3 text-sm text-on-surface-variant">
                        {{ $seller->country }} · <span class="font-semibold text-on-surface">{{ $followers }}</span> followers ·
                        Member since <span class="font-mono text-xs uppercase">{{ $seller->created_at->format('Y') }}</span>
                    </p>
                </div>
            </div>
            @auth
                @if(auth()->id() !== $seller->user_id)
                    <form method="POST" action="{{ route('sellers.follow', $seller) }}">
                        @csrf
                        <button class="flex items-center gap-2 rounded-xl border border-primary px-5 py-3 font-semibold text-primary transition-all hover:bg-primary hover:text-on-primary active:scale-95">
                            <span class="material-symbols-outlined text-[20px]">person_add</span>
                            Follow seller
                        </button>
                    </form>
                @endif
            @endauth
        </div>
    </div>
</div>
<div class="mx-auto max-w-7xl px-6 py-12">
    <h2 class="mb-6 font-display text-2xl font-semibold tracking-tight">Products</h2>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <p class="col-span-full text-on-surface-variant">No published products yet.</p>
        @endforelse
    </div>
    <div class="mt-10">{{ $products->links() }}</div>
</div>
</x-marketplace-layout>
