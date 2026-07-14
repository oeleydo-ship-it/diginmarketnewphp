@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $title ?? config('marketplace.seo_meta_title', config('app.name', 'DiginMarket').' — Premium Digital Assets') }}</title>
<meta name="description" content="{{ $description ?? config('marketplace.seo_meta_description', 'Discover reviewed digital products from independent creators.') }}">
@if(config('marketplace.seo_meta_keywords'))<meta name="keywords" content="{{ config('marketplace.seo_meta_keywords') }}">@endif
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:title" content="{{ $title ?? 'DiginMarket — Premium Digital Assets' }}">
<meta property="og:description" content="{{ $description ?? 'A curated marketplace for digital products.' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="website">
<script>{!! \App\Http\Middleware\SecurityHeaders::THEME_BOOTSTRAP !!}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-xl focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-on-primary">Skip to main content</a>
@php
    $cartCount = auth()->check() ? (auth()->user()->cart()->first()?->items()->count() ?? 0) : 0;
    $isSeller = auth()->check() && auth()->user()->hasRole('seller');
@endphp
<header data-header class="fixed inset-x-0 top-0 z-50 border-b border-outline-variant bg-surface/80 backdrop-blur-md transition-shadow">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-6">
        <div class="flex items-center gap-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-display text-2xl font-bold tracking-tight text-primary">
                @if($brandLogo = config('marketplace.logo_path'))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($brandLogo) }}" alt="{{ config('app.name', 'DiginMarket') }}" class="h-10 w-auto">
                @else
                    {{ config('app.name', 'DiginMarket') }}
                @endif
            </a>
            <nav class="hidden items-center gap-6 md:flex">
                <a href="{{ route('products.index') }}" class="text-[15px] font-semibold {{ request()->routeIs('products.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-on-surface-variant transition-colors hover:text-primary' }}">Browse</a>
                <a href="{{ route('home') }}#categories" class="text-[15px] font-semibold text-on-surface-variant transition-colors hover:text-primary">Categories</a>
                <a href="{{ route('support.index') }}" class="text-[15px] font-semibold text-on-surface-variant transition-colors hover:text-primary">Support</a>
            </nav>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <form action="{{ route('products.index') }}" method="GET" class="relative hidden lg:block">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-on-surface-variant">search</span>
                <input name="q" value="{{ request('q') }}" type="text" placeholder="Search assets..." aria-label="Search assets"
                    class="w-64 rounded-xl border border-outline-variant bg-surface-container-low py-2 pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/20">
            </form>
            <a href="{{ auth()->check() ? ($isSeller ? route('seller.products.index') : route('seller.apply')) : route('register') }}"
                class="hidden rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95 sm:block">Sell Assets</a>
            <button data-theme-toggle type="button" class="p-2 text-on-surface-variant transition-colors hover:text-primary" aria-label="Toggle dark mode" aria-pressed="false">
                <span data-theme-icon class="material-symbols-outlined" aria-hidden="true">dark_mode</span>
            </button>
            @auth
                <a href="{{ route('cart.index') }}" class="relative p-2 text-on-surface-variant transition-colors hover:text-primary" aria-label="Cart">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    @if($cartCount > 0)
                        <span class="absolute right-0 top-0 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-on-primary">{{ $cartCount }}</span>
                    @endif
                </a>
                <a href="{{ route('wishlist.index') }}" class="hidden p-2 text-on-surface-variant transition-colors hover:text-primary sm:block" aria-label="Wishlist">
                    <span class="material-symbols-outlined">favorite</span>
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-xl px-2 py-1.5 transition-colors hover:bg-surface-container-low" aria-label="Dashboard">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-container font-display text-sm font-bold text-on-primary-container">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant transition-colors hover:text-primary">Sign in</a>
            @endauth
            <button data-nav-toggle class="p-2 text-on-surface-variant md:hidden" aria-label="Menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
    <nav data-nav-menu class="hidden border-t border-outline-variant bg-surface px-6 py-4 md:hidden">
        <div class="flex flex-col gap-3 text-sm font-semibold text-on-surface-variant">
            <a href="{{ route('products.index') }}">Browse</a>
            <a href="{{ route('home') }}#categories">Categories</a>
            <a href="{{ route('support.index') }}">Support</a>
            <a href="{{ auth()->check() ? ($isSeller ? route('seller.products.index') : route('seller.apply')) : route('register') }}" class="text-primary">Sell Assets</a>
        </div>
    </nav>
</header>
<main id="main-content" class="pt-20">
    @if(session('status'))
        <div class="mx-auto mt-6 max-w-7xl px-6">
            <div class="flex items-center gap-3 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                {{ session('status') }}
            </div>
        </div>
    @endif
    {{ $slot }}
</main>
<footer class="mt-24 border-t border-outline-variant bg-surface-container-highest">
    <div class="mx-auto max-w-7xl px-6 py-14">
        <div class="mb-12 grid grid-cols-1 gap-10 md:grid-cols-4 lg:grid-cols-5">
            <div class="col-span-1 lg:col-span-2">
                <span class="mb-4 block font-display text-lg font-bold text-primary">{{ config('app.name', 'DiginMarket') }}</span>
                <p class="mb-6 max-w-xs text-sm text-on-surface-variant">A curated marketplace for professional digital assets, scripts, and themes from independent creators.</p>
                <div class="flex gap-2" aria-hidden="true">
                    <span class="material-symbols-outlined rounded-full p-2 text-primary">public</span>
                    <span class="material-symbols-outlined rounded-full p-2 text-primary">alternate_email</span>
                    <span class="material-symbols-outlined rounded-full p-2 text-primary">forum</span>
                </div>
            </div>
            <div>
                <h5 class="mb-5 text-[15px] font-semibold">Marketplace</h5>
                <ul class="flex flex-col gap-2.5">
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('products.index') }}">All Assets</a></li>
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('products.index', ['sort' => 'newest']) }}">New Releases</a></li>
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('products.index', ['sort' => 'popular']) }}">Best Sellers</a></li>
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('home') }}#categories">Categories</a></li>
                </ul>
            </div>
            <div>
                <h5 class="mb-5 text-[15px] font-semibold">Legal</h5>
                <ul class="flex flex-col gap-2.5">
                    @forelse(\App\Models\MenuItem::forLocation('footer-legal') as $item)
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ $item->url }}">{{ $item->label }}</a></li>
                    @empty
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a></li>
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('pages.show', 'terms-of-service') }}">Terms of Service</a></li>
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('pages.show', 'seller-agreement') }}">Seller Agreement</a></li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h5 class="mb-5 text-[15px] font-semibold">Support</h5>
                <ul class="flex flex-col gap-2.5">
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('support.index') }}">Help Center</a></li>
                    @foreach(\App\Models\MenuItem::forLocation('footer-resources') as $item)
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ $item->url }}">{{ $item->label }}</a></li>
                    @endforeach
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('sitemap') }}">Sitemap</a></li>
                </ul>
            </div>
        </div>
        <div class="flex flex-col items-center justify-between gap-4 border-t border-outline-variant pt-6 md:flex-row">
            <span class="text-sm text-on-surface-variant">© {{ date('Y') }} DiginMarket. All rights reserved.</span>
            <div class="flex gap-5 text-outline">
                <span class="material-symbols-outlined">credit_card</span>
                <span class="material-symbols-outlined">account_balance_wallet</span>
                <span class="material-symbols-outlined">lock</span>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
