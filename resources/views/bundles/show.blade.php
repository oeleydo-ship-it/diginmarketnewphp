<x-marketplace-layout :title="$bundle->title.' — Bundle'" :description="str($bundle->description ?? '')->limit(150)->toString()">
<div class="mx-auto max-w-5xl px-6 py-14">
    <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Product bundle</p>
    <h1 class="mt-2 font-display text-4xl font-bold tracking-tight">{{ $bundle->title }}</h1>
    <p class="mt-2 text-sm text-on-surface-variant">by <a class="font-semibold text-primary hover:underline" href="{{ $bundle->seller->sellerProfile ? route('sellers.show', $bundle->seller->sellerProfile->username) : '#' }}">{{ $bundle->seller->sellerProfile?->display_name ?? $bundle->seller->name }}</a></p>
    @if($bundle->description)<p class="mt-4 max-w-2xl text-on-surface-variant">{{ $bundle->description }}</p>@endif

    <div class="mt-10 grid items-start gap-8 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @foreach($bundle->products as $product)
                <a href="{{ route('products.show', $product->slug) }}" class="flex items-center justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 transition-colors hover:border-primary">
                    <div>
                        <h2 class="font-semibold">{{ $product->title }}</h2>
                        <p class="text-sm text-on-surface-variant">{{ $product->category?->name }} · {{ str($product->short_description)->limit(90) }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-sm">${{ number_format((float) $product->regular_price, 2) }}</span>
                </a>
            @endforeach
        </div>
        <aside class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 lg:sticky lg:top-24">
            <p class="text-sm text-on-surface-variant">Bundle price</p>
            <p class="mt-1 font-display text-4xl font-bold text-primary">${{ number_format((float) $bundle->price, 2) }}</p>
            @if($compareAt > (float) $bundle->price)
                <p class="mt-1 text-sm text-on-surface-variant">
                    <span class="line-through">${{ number_format($compareAt, 2) }}</span>
                    <span class="ml-1 font-semibold text-secondary">save {{ round((1 - (float) $bundle->price / $compareAt) * 100) }}%</span>
                </p>
            @endif
            <p class="mt-4 text-xs text-on-surface-variant">Includes a Regular License for each of the {{ $bundle->products->count() }} products, with individual downloads and license keys.</p>
            @auth
                <form method="POST" action="{{ route('bundles.buy', $bundle) }}" class="mt-6">@csrf
                    <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">
                        <span class="material-symbols-outlined">shopping_bag</span> Buy bundle
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-semibold text-on-primary transition-all hover:opacity-90">Sign in to buy</a>
            @endauth
        </aside>
    </div>
</div>
</x-marketplace-layout>
