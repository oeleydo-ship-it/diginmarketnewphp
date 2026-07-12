<x-marketplace-layout :title="$order->number . ' — DiginMarket'">
<x-customer-panel>
    <header class="mb-8">
        <a href="{{ route('purchases.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> All purchases
        </a>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold tracking-tight">{{ $order->number }}</h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    Placed <span class="font-mono text-xs uppercase">{{ $order->created_at->format('M j, Y H:i') }}</span>
                    · Total <span class="font-semibold text-on-surface">${{ number_format($order->total, 2) }} {{ $order->currency }}</span>
                </p>
            </div>
            @php($paid = $order->payment_status === 'paid')
            <span class="rounded px-3 py-1.5 font-mono text-xs font-bold uppercase tracking-wider {{ $paid ? 'bg-secondary-container/40 text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed-variant' }}">
                {{ str($order->payment_status)->headline() }}
            </span>
        </div>
    </header>
    <div class="space-y-6">
        @foreach($order->items as $item)
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                    <div>
                        <h2 class="font-semibold text-primary">{{ $item->product_title }}</h2>
                        <p class="mt-1 font-mono text-[11px] uppercase tracking-wider text-on-surface-variant">{{ $item->license_name }}</p>
                    </div>
                    <span class="font-display text-xl font-bold">${{ number_format($item->total, 2) }}</span>
                </div>
                @if($item->license)
                    <div class="border-t border-outline-variant bg-surface px-6 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="mb-1 font-mono text-[10px] uppercase tracking-wider text-on-surface-variant">License Key</p>
                                <code class="rounded bg-surface-container px-3 py-1.5 font-mono text-sm font-medium text-on-surface">{{ $item->license->license_key }}</code>
                            </div>
                            @if($item->license->status === 'active')
                                <a href="{{ $item->license->download_url }}"
                                    class="flex items-center gap-2 rounded-lg bg-primary px-5 py-3 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">
                                    <span class="material-symbols-outlined">download</span>
                                    Download Main File
                                </a>
                            @else
                                <span class="flex items-center gap-2 rounded-lg bg-error-container px-4 py-2 text-sm font-semibold text-on-error-container">
                                    <span class="material-symbols-outlined text-[18px]">block</span>
                                    License {{ $item->license->status }} — downloads disabled
                                </span>
                            @endif
                        </div>
                        @if($item->license->status === 'active')
                            <div class="mt-6 grid gap-5 border-t border-outline-variant pt-6 md:grid-cols-3">
                                <form method="POST" action="{{ route('reviews.store', $item) }}" class="space-y-2 rounded-lg border border-outline-variant/50 bg-surface-container-low p-4">
                                    @csrf
                                    <h3 class="flex items-center gap-1.5 text-sm font-bold"><span class="material-symbols-outlined text-[18px] text-tertiary">star</span> Leave a review</h3>
                                    <select name="rating" class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                                        @for($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} stars</option>@endfor
                                    </select>
                                    <input name="title" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Review title">
                                    <textarea name="content" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Your experience"></textarea>
                                    <button class="text-sm font-semibold text-primary hover:underline">Publish review</button>
                                </form>
                                <form method="POST" action="{{ route('support.store', $item->license) }}" class="space-y-2 rounded-lg border border-outline-variant/50 bg-surface-container-low p-4">
                                    @csrf
                                    <h3 class="flex items-center gap-1.5 text-sm font-bold"><span class="material-symbols-outlined text-[18px] text-secondary">support_agent</span> Open support ticket</h3>
                                    <input name="subject" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Subject">
                                    <textarea name="message" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="How can the seller help?"></textarea>
                                    <button class="text-sm font-semibold text-primary hover:underline">Open ticket</button>
                                </form>
                                <form method="POST" action="{{ route('disputes.store', $item) }}" class="space-y-2 rounded-lg border border-outline-variant/50 bg-surface-container-low p-4">
                                    @csrf
                                    <h3 class="flex items-center gap-1.5 text-sm font-bold"><span class="material-symbols-outlined text-[18px] text-tertiary">gavel</span> Open dispute</h3>
                                    <select name="type" class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="quality">Quality issue</option>
                                        <option value="not_as_described">Not as described</option>
                                        <option value="not_delivered">Not delivered</option>
                                        <option value="unauthorized">Unauthorized purchase</option>
                                    </select>
                                    <textarea name="description" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Describe the problem (min 20 characters)"></textarea>
                                    <button class="text-sm font-semibold text-tertiary hover:underline">Open dispute</button>
                                </form>
                                <form method="POST" action="{{ route('refunds.store', $item) }}" class="space-y-2 rounded-lg border border-outline-variant/50 bg-surface-container-low p-4">
                                    @csrf
                                    <h3 class="flex items-center gap-1.5 text-sm font-bold"><span class="material-symbols-outlined text-[18px] text-error">currency_exchange</span> Request refund</h3>
                                    <input name="reason" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Reason">
                                    <textarea name="description" required class="w-full rounded-lg border border-outline-variant bg-surface p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Explain the issue"></textarea>
                                    <button class="text-sm font-semibold text-error hover:underline">Submit request</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex items-center gap-2 border-t border-outline-variant bg-tertiary-fixed/40 px-6 py-4 text-sm font-medium text-on-tertiary-fixed-variant">
                        <span class="material-symbols-outlined text-[18px]">hourglass_top</span>
                        License pending webhook-confirmed payment.
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-customer-panel>
</x-marketplace-layout>
