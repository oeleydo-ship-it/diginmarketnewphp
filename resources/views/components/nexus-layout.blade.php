@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('locales.available.'.app()->getLocale().'.dir', 'ltr') }}">
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
@if(session()->has(\App\Http\Controllers\ImpersonationController::SESSION_KEY))
<div class="fixed inset-x-0 bottom-0 z-[70] flex flex-wrap items-center justify-center gap-3 bg-amber-500 px-4 py-2.5 text-sm font-semibold text-amber-950">
    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">theater_comedy</span>
    Impersonating {{ auth()->user()?->name }} — this session ends automatically.
    <form method="POST" action="{{ route('impersonation.stop') }}">@csrf<button class="rounded-lg bg-amber-950 px-3 py-1 text-xs font-bold text-amber-50">Stop impersonating</button></form>
</div>
@endif
@php
    $cartCount = auth()->check() ? (auth()->user()->cart()->first()?->items()->count() ?? 0) : 0;
    $isSeller = auth()->check() && auth()->user()->hasRole('seller');
    // Guests are sent to register normally, or to login when registration is disabled.
    $guestSellUrl = \App\Models\Setting::enabled('features.registration') ? route('register') : route('login');
    $blogEnabled = \App\Models\Setting::enabled('features.blog');
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
                @php($headerMenu = \App\Models\MenuItem::forLocation('header'))
                @if($headerMenu->isNotEmpty())
                    @foreach($headerMenu as $item)
                        <a href="{{ $item->url }}" class="text-[15px] font-semibold {{ url()->current() === url($item->url) ? 'border-b-2 border-primary pb-1 text-primary' : 'text-on-surface-variant transition-colors hover:text-primary' }}">{{ $item->label }}</a>
                    @endforeach
                @else
                    <a href="{{ route('products.index') }}" class="text-[15px] font-semibold {{ request()->routeIs('products.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-on-surface-variant transition-colors hover:text-primary' }}">{{ __('messages.nav.browse') }}</a>
                    <a href="{{ route('home') }}#categories" class="text-[15px] font-semibold text-on-surface-variant transition-colors hover:text-primary">{{ __('messages.nav.categories') }}</a>
                    <a href="{{ route('bundles.index') }}" class="text-[15px] font-semibold {{ request()->routeIs('bundles.*') ? 'border-b-2 border-primary pb-1 text-primary' : 'text-on-surface-variant transition-colors hover:text-primary' }}">{{ __('messages.nav.bundles') }}</a>
                    <a href="{{ route('support.index') }}" class="text-[15px] font-semibold text-on-surface-variant transition-colors hover:text-primary">{{ __('messages.nav.support') }}</a>
                @endif
            </nav>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <form action="{{ route('products.index') }}" method="GET" class="relative hidden lg:block">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-on-surface-variant">search</span>
                <input name="q" value="{{ request('q') }}" type="text" placeholder="{{ __('messages.nav.search') }}" aria-label="{{ __('messages.nav.search') }}"
                    class="w-64 rounded-xl border border-outline-variant bg-surface-container-low py-2 pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/20">
            </form>
            <a href="{{ auth()->check() ? ($isSeller ? route('seller.dashboard') : route('seller.apply')) : $guestSellUrl }}"
                class="hidden rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95 sm:block">{{ __('messages.nav.sell') }}</a>
            @php($locales = (array) config('locales.available'))
            @if(count($locales) > 1)
                <details class="group relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1 p-2 text-on-surface-variant transition-colors hover:text-primary [&::-webkit-details-marker]:hidden" aria-label="{{ __('messages.nav.language') }}">
                        <span class="material-symbols-outlined text-[22px]">language</span>
                        <span class="hidden text-xs font-semibold uppercase sm:inline">{{ app()->getLocale() }}</span>
                    </summary>
                    <div class="absolute end-0 z-50 mt-2 w-40 overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest py-1 shadow-lg">
                        @foreach($locales as $code => $meta)
                            <form method="POST" action="{{ route('locale.update', $code) }}">@csrf
                                <button type="submit" class="flex w-full items-center justify-between px-4 py-2 text-sm {{ app()->getLocale() === $code ? 'font-bold text-primary' : 'text-on-surface hover:bg-surface-container-low' }}">
                                    <span>{{ $meta['native'] }}</span>
                                    @if(app()->getLocale() === $code)<span class="material-symbols-outlined text-[18px]">check</span>@endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </details>
            @endif
            <button data-theme-toggle type="button" class="p-2 text-on-surface-variant transition-colors hover:text-primary" aria-label="{{ __('messages.nav.toggle_theme') }}" aria-pressed="false">
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
                <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-on-surface-variant transition-colors hover:text-primary">{{ __('messages.nav.login') }}</a>
            @endauth
            <button data-nav-toggle class="p-2 text-on-surface-variant md:hidden" aria-label="Menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>
    <nav data-nav-menu class="hidden border-t border-outline-variant bg-surface px-6 py-4 md:hidden">
        <div class="flex flex-col gap-3 text-sm font-semibold text-on-surface-variant">
            @php($mobileHeaderMenu = \App\Models\MenuItem::forLocation('header'))
            @if($mobileHeaderMenu->isNotEmpty())
                @foreach($mobileHeaderMenu as $item)<a href="{{ $item->url }}">{{ $item->label }}</a>@endforeach
            @else
                <a href="{{ route('products.index') }}">{{ __('messages.nav.browse') }}</a>
                <a href="{{ route('home') }}#categories">{{ __('messages.nav.categories') }}</a>
                <a href="{{ route('bundles.index') }}">{{ __('messages.nav.bundles') }}</a>
                <a href="{{ route('support.index') }}">{{ __('messages.nav.support') }}</a>
            @endif
            <a href="{{ auth()->check() ? ($isSeller ? route('seller.dashboard') : route('seller.apply')) : $guestSellUrl }}" class="text-primary">{{ __('messages.nav.sell') }}</a>
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
                @php($socials=collect([['social.website','public','Website'],['social.twitter','alternate_email','X (Twitter)'],['social.community','forum','Community']])->map(fn($s)=>['url'=>\App\Models\Setting::get($s[0]),'icon'=>$s[1],'label'=>$s[2]])->filter(fn($s)=>$s['url']))
                @if($socials->isNotEmpty())
                <div class="flex gap-2">
                    @foreach($socials as $social)
                    <a href="{{ $social['url'] }}" rel="noopener" target="_blank" aria-label="{{ $social['label'] }}" class="rounded-full p-2 text-primary transition-colors hover:bg-primary-container/20"><span class="material-symbols-outlined" aria-hidden="true">{{ $social['icon'] }}</span></a>
                    @endforeach
                </div>
                @endif
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
                    @forelse(\App\Models\MenuItem::forLocation('footer-legal')->reject(fn ($item) => !$blogEnabled && str_contains($item->url, '/blog')) as $item)
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
                    @foreach(\App\Models\MenuItem::forLocation('footer-resources')->reject(fn ($item) => !$blogEnabled && str_contains($item->url, '/blog')) as $item)
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ $item->url }}">{{ $item->label }}</a></li>
                    @endforeach
                    <li><a class="text-sm text-on-surface-variant transition-colors hover:text-primary" href="{{ route('sitemap') }}">Sitemap</a></li>
                </ul>
            </div>
        </div>
        <div class="flex flex-col items-center justify-between gap-4 border-t border-outline-variant pt-6 md:flex-row">
            <span class="text-sm text-on-surface-variant">© {{ date('Y') }} {{ config('app.name', 'DiginMarket') }}. All rights reserved.</span>
            <div class="flex gap-5 text-outline">
                <span class="material-symbols-outlined">credit_card</span>
                <span class="material-symbols-outlined">account_balance_wallet</span>
                <span class="material-symbols-outlined">lock</span>
            </div>
        </div>
    </div>
</footer>

@if(\App\Models\Setting::enabled('features.cookie_consent', true) && ! request()->cookie('dm_cookie_consent'))
    <div data-cookie-banner class="fixed inset-x-0 bottom-0 z-[90] border-t border-outline-variant bg-surface-container-lowest/95 p-4 shadow-2xl backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <p class="text-sm text-on-surface-variant">{{ __('messages.cookies.notice') }}</p>
            <div class="flex shrink-0 gap-2">
                <button data-cookie-choice="essential" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-variant transition-colors hover:bg-surface-container">{{ __('messages.cookies.essential') }}</button>
                <button data-cookie-choice="all" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary transition-all hover:opacity-90">{{ __('messages.cookies.accept') }}</button>
            </div>
        </div>
    </div>
@endif

@if(config('services.tawk.property_id'))
    {{-- app.js reads this and injects the tawk.to script; CSP allows *.tawk.to only when configured. --}}
    <meta name="tawk-embed" content="{{ config('services.tawk.property_id') }}/{{ config('services.tawk.widget_id') ?: 'default' }}">
@endif
</body>
</html>
