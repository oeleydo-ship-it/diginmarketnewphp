@props(['title' => 'Customer Portal', 'subtitle' => 'Manage your assets'])
<div class="mx-auto flex max-w-7xl flex-col gap-8 px-6 py-10 lg:flex-row">
    <aside class="w-full shrink-0 lg:w-[260px]">
        <div class="rounded-xl border border-outline-variant bg-surface-container-low p-4 lg:sticky lg:top-24">
            <div class="mb-6 px-2 pt-2">
                <h2 class="font-display text-lg font-bold text-on-surface">{{ $title }}</h2>
                <p class="text-sm text-on-surface-variant">{{ $subtitle }}</p>
            </div>
            <nav class="flex flex-col gap-1">
                @php($links = [
                    ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'space_dashboard', 'label' => 'Overview'],
                    ['route' => 'purchases.index', 'match' => 'purchases.*', 'icon' => 'receipt_long', 'label' => 'Purchases'],
                    ['route' => 'downloads.index', 'match' => 'downloads.*', 'icon' => 'download', 'label' => 'Downloads'],
                    ['route' => 'wishlist.index', 'match' => 'wishlist.*', 'icon' => 'favorite', 'label' => 'Wishlist'],
                    ['route' => 'support.index', 'match' => 'support.*', 'icon' => 'support_agent', 'label' => 'Support'],
                    ['route' => 'affiliates.show', 'match' => 'affiliates.*', 'icon' => 'share', 'label' => 'Affiliates'],
                    ['route' => 'notifications.preferences', 'match' => 'notifications.*', 'icon' => 'notifications', 'label' => 'Notifications'],
                    ['route' => 'account.security', 'match' => 'account.security', 'icon' => 'lock', 'label' => 'Security'],
                ])
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[15px] transition-colors {{ request()->routeIs($link['match']) ? 'bg-secondary-container/40 font-semibold text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs($link['match']) ? 'icon-fill' : '' }}">{{ $link['icon'] }}</span>
                        {{ $link['label'] }}
                    </a>
                @endforeach
                @if(auth()->user()->hasRole('seller'))
                    <a href="{{ route('seller.products.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[15px] text-on-surface-variant transition-colors hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-[20px]">storefront</span>
                        Seller Studio
                    </a>
                @endif
                @if(auth()->user()->hasRole('administrator'))
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[15px] text-on-surface-variant transition-colors hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-[20px]">shield_person</span>
                        Admin Panel
                    </a>
                @endif
            </nav>
            <div class="mt-6 border-t border-outline-variant pt-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-[15px] text-on-surface-variant transition-colors hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-[20px]">logout</span>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </aside>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
