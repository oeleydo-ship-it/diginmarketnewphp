<x-marketplace-layout :title="$ticket->number . ' — DiginMarket'">
<x-customer-panel>
    <header class="mb-8">
        <a href="{{ route('support.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> All tickets
        </a>
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">{{ $ticket->number }} · {{ str($ticket->status)->headline() }}</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ $ticket->subject }}</h1>
    </header>
    <div class="space-y-4">
        @foreach($ticket->messages as $message)
            @php($mine = $message->user_id === auth()->id())
            <div class="rounded-xl border p-5 {{ $mine ? 'ml-8 border-primary/20 bg-primary-container/10' : 'mr-8 border-outline-variant bg-surface-container-lowest' }}">
                <p class="text-[15px] leading-relaxed text-on-surface">{{ $message->message }}</p>
                <p class="mt-3 font-mono text-[11px] uppercase tracking-wider text-on-surface-variant">
                    {{ $mine ? 'You' : 'Seller' }} · {{ $message->created_at->format('M j, Y H:i') }}
                </p>
            </div>
        @endforeach
    </div>
    <form method="POST" action="{{ route('support.reply', $ticket) }}" class="mt-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
        @csrf
        <textarea name="message" required rows="4" placeholder="Write a reply"
            class="w-full rounded-lg border border-outline-variant bg-surface p-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
        <button class="mt-3 flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
            <span class="material-symbols-outlined text-[20px]">send</span>
            Send reply
        </button>
    </form>
</x-customer-panel>
</x-marketplace-layout>
