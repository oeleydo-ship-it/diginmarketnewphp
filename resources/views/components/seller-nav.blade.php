@php($tabs = [
    ['route' => 'seller.dashboard', 'match' => 'seller.dashboard', 'icon' => 'space_dashboard', 'label' => 'Overview'],
    ['route' => 'seller.products.index', 'match' => 'seller.products.*', 'icon' => 'inventory_2', 'label' => 'Products'],
    ['route' => 'seller.sales', 'match' => 'seller.sales', 'icon' => 'receipt_long', 'label' => 'Sales'],
    ['route' => 'seller.customers', 'match' => 'seller.customers', 'icon' => 'group', 'label' => 'Customers'],
    ['route' => 'seller.coupons.index', 'match' => 'seller.coupons.*', 'icon' => 'sell', 'label' => 'Coupons'],
    ['route' => 'seller.finance', 'match' => 'seller.finance', 'icon' => 'account_balance_wallet', 'label' => 'Earnings'],
    ['route' => 'seller.subscription.index', 'match' => 'seller.subscription.*', 'icon' => 'workspace_premium', 'label' => 'Plan'],
    ['route' => 'seller.settings.edit', 'match' => 'seller.settings.*', 'icon' => 'settings', 'label' => 'Settings'],
])
<div class="mb-8 flex gap-1 overflow-x-auto border-b border-outline-variant">
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
            class="flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors {{ request()->routeIs($tab['match']) ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
            <span class="material-symbols-outlined text-[18px]">{{ $tab['icon'] }}</span>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
