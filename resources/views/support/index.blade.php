<x-marketplace-layout title="Support Tickets — DiginMarket">
<x-customer-panel>
    <header class="mb-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">Support Tickets</h1>
        <p class="mt-1 text-on-surface-variant">Conversations with sellers about your purchases.</p>
    </header>
    <div class="space-y-3">
        @forelse($tickets as $ticket)
            <a href="{{ route('support.show', $ticket) }}"
                class="group flex items-center justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 transition-all hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-container/20 text-primary">
                        <span class="material-symbols-outlined">forum</span>
                    </span>
                    <div class="min-w-0">
                        <strong class="block truncate font-semibold text-on-surface group-hover:text-primary">{{ $ticket->subject }}</strong>
                        <p class="font-mono text-xs uppercase tracking-wider text-on-surface-variant">{{ $ticket->number }}</p>
                    </div>
                </div>
                @php($open = in_array($ticket->status, ['open', 'awaiting_seller', 'awaiting_customer'], true))
                <span class="shrink-0 rounded px-2 py-1 font-mono text-[10px] font-bold uppercase tracking-wider {{ $open ? 'bg-primary-container/20 text-primary' : 'bg-outline-variant/30 text-on-surface-variant' }}">
                    {{ str($ticket->status)->headline() }}
                </span>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-outline-variant p-14 text-center">
                <span class="material-symbols-outlined mb-3 text-[40px] text-outline">support_agent</span>
                <p class="text-on-surface-variant">Open support from an eligible purchase.</p>
                <a href="{{ route('purchases.index') }}" class="mt-4 inline-block rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90">My purchases</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $tickets->links() }}</div>
</x-customer-panel>
</x-marketplace-layout>
