<x-marketplace-layout title="Dashboard — DiginMarket">
<x-customer-panel>
    <header class="mb-8">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Account Overview</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">Hello, {{ $user->name }}</h1>
    </header>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-outline-variant bg-surface-container p-6">
            <header class="mb-4 flex items-center justify-between">
                <span class="font-semibold">Roles</span>
                <span class="material-symbols-outlined text-primary">badge</span>
            </header>
            <p class="text-xl font-bold">{{ $user->roles->pluck('name')->map(fn ($r) => str($r)->headline())->join(', ') }}</p>
            <p class="mt-1 text-sm text-on-surface-variant">Member since <span class="font-mono text-xs uppercase">{{ $user->created_at->format('M Y') }}</span></p>
        </div>
        <a href="{{ route('purchases.index') }}" class="group rounded-xl border border-outline-variant bg-surface-container p-6 transition-all hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5">
            <header class="mb-4 flex items-center justify-between">
                <span class="font-semibold">Purchases</span>
                <span class="material-symbols-outlined text-secondary">download</span>
            </header>
            <p class="text-xl font-bold text-primary group-hover:underline">My downloads</p>
            <p class="mt-1 text-sm text-on-surface-variant">Orders, files, and license keys</p>
        </a>
        <div class="rounded-xl border border-outline-variant bg-surface-container p-6">
            <header class="mb-4 flex items-center justify-between">
                <span class="font-semibold">Seller Profile</span>
                <span class="material-symbols-outlined text-tertiary">storefront</span>
            </header>
            @php($sellerStatus = $user->sellerProfile?->status->value)
            @if($sellerStatus)
                <span class="rounded px-2 py-1 font-mono text-[10px] font-bold uppercase tracking-wider {{ $sellerStatus === 'approved' ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-primary-container/20 text-primary' }}">{{ $sellerStatus }}</span>
                <p class="mt-2 text-sm text-on-surface-variant">
                    @if($sellerStatus === 'approved')<a href="{{ route('seller.products.index') }}" class="font-semibold text-primary hover:underline">Open Seller Studio</a>@else Application under review @endif
                </p>
            @else
                <p class="text-xl font-bold">Not started</p>
                <a href="{{ route('seller.apply') }}" class="mt-1 inline-block text-sm font-semibold text-primary hover:underline">Apply to sell →</a>
            @endif
        </div>
    </div>
    <section class="mt-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
        <h2 class="mb-4 font-display text-lg font-semibold">Quick Actions</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @php($actions = [
                ['route' => route('products.index'), 'icon' => 'storefront', 'label' => 'Browse assets'],
                ['route' => route('wishlist.index'), 'icon' => 'favorite', 'label' => 'My wishlist'],
                ['route' => route('support.index'), 'icon' => 'support_agent', 'label' => 'Support tickets'],
                ['route' => route('notifications.preferences'), 'icon' => 'notifications', 'label' => 'Notifications'],
            ])
            @foreach($actions as $action)
                <a href="{{ $action['route'] }}" class="flex items-center gap-3 rounded-lg border border-outline-variant/50 bg-surface p-4 text-sm font-semibold transition-colors hover:border-primary/40 hover:text-primary">
                    <span class="material-symbols-outlined text-primary">{{ $action['icon'] }}</span>
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </section>
</x-customer-panel>
</x-marketplace-layout>
