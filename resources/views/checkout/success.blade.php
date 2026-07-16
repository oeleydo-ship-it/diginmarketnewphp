<x-marketplace-layout title="Order Confirmed — DiginMarket">
@php($paid = $order->payment_status === 'paid')
<div class="relative overflow-hidden px-6 py-20">
    @if($paid)<canvas id="confetti" class="pointer-events-none fixed inset-0 z-10 h-full w-full"></canvas>@endif
    <div class="pointer-events-none absolute left-1/4 top-1/4 h-96 w-96 rounded-full bg-primary/5 blur-[100px]"></div>
    <div class="pointer-events-none absolute bottom-1/4 right-1/4 h-96 w-96 rounded-full bg-secondary/5 blur-[100px]"></div>
    <div class="relative z-20 mx-auto w-full max-w-2xl">
        <div class="mb-12 text-center">
            <div class="mb-6 inline-flex h-24 w-24 items-center justify-center rounded-full {{ $paid ? 'animate-bounce bg-secondary-container text-on-secondary-container shadow-lg shadow-secondary/10' : 'bg-tertiary-fixed text-on-tertiary-fixed-variant' }}">
                <span class="material-symbols-outlined !text-[48px]">{{ $paid ? 'check_circle' : 'hourglass_top' }}</span>
            </div>
            <h1 class="mb-2 font-display text-4xl font-bold tracking-tight text-primary md:text-5xl">{{ $paid ? 'Payment Successful' : 'Confirming Your Payment' }}</h1>
            <p class="mx-auto max-w-md text-on-surface-variant">
                {{ $paid ? 'Thank you for your purchase! Your order' : 'We are waiting for your payment provider to confirm order' }}
                <span class="rounded bg-surface-container px-2 py-0.5 font-mono text-xs font-medium">{{ $order->number }}</span>
                {{ $paid ? 'has been confirmed.' : '— this usually takes a few seconds.' }}
            </p>
            <div class="mt-4 flex items-center justify-center gap-2 text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-[18px]">{{ $paid ? 'mail' : 'sync' }}</span>
                <span>{{ $paid ? 'A receipt has been sent to your registered email address.' : 'Downloads and licenses are released only after a verified webhook.' }}</span>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="flex flex-col justify-between rounded-xl border border-outline-variant bg-surface-container-lowest/70 p-6 backdrop-blur-md">
                <div>
                    <h2 class="mb-4 font-mono text-xs font-medium uppercase tracking-wider text-on-surface-variant">Order Summary</h2>
                    <div class="space-y-3">
                        @foreach($order->items as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-on-surface">{{ $item->product_title }}</h3>
                                    <p class="font-mono text-[11px] uppercase tracking-wider text-on-surface-variant">{{ $item->license_name }}</p>
                                </div>
                                <span class="font-mono text-sm">${{ number_format($item->total, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between border-t border-outline-variant pt-4">
                    <span class="text-sm text-on-surface-variant">Total {{ $paid ? 'Paid' : 'Due' }}</span>
                    <span class="font-display text-2xl font-bold">${{ number_format($order->total, 2) }} <span class="text-sm font-medium text-on-surface-variant">{{ $order->currency }}</span></span>
                </div>
            </div>
            <div class="flex flex-col justify-between rounded-xl bg-primary p-6 shadow-xl shadow-primary/20">
                <div>
                    <h2 class="mb-4 font-mono text-xs font-medium uppercase tracking-wider text-on-primary-container">Next Steps</h2>
                    <p class="mb-6 text-sm text-on-primary-container/80">
                        {{ $paid ? 'Your assets are ready for deployment. Access your files and license keys below.' : 'Once payment confirms, your files and license keys unlock automatically.' }}
                    </p>
                </div>
                <div class="space-y-2">
                    <a href="{{ route('purchases.show', $order) }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-on-primary py-3 font-semibold text-primary transition-all hover:bg-surface-container-low active:scale-95">
                        <span class="material-symbols-outlined">{{ $paid ? 'download' : 'receipt_long' }}</span>
                        {{ $paid ? 'Access Downloads' : 'View Order Status' }}
                    </a>
                    <a href="{{ route('purchases.index') }}" class="flex w-full items-center justify-center gap-2 rounded-lg border border-on-primary/30 py-3 font-semibold text-on-primary transition-all hover:bg-white/10 active:scale-95">
                        <span class="material-symbols-outlined">description</span>
                        My Purchases
                    </a>
                </div>
            </div>
        </div>
        <div class="mt-12 text-center">
            <a class="group inline-flex items-center gap-2 font-semibold text-primary hover:underline" href="{{ route('dashboard') }}">
                Go to Customer Dashboard
                <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
            </a>
            <div class="mt-6 border-t border-outline-variant pt-6">
                <p class="text-sm text-on-surface-variant">
                    Need help? Visit our <a class="text-primary hover:underline" href="{{ route('support.index') }}">Help Center</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@if($paid)
<script>
(() => {
    const canvas = document.getElementById('confetti');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const colors = ['#3525cd', '#006c49', '#684000', '#4f46e5', '#6ffbbe'];
    const resize = () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; };
    resize();
    window.addEventListener('resize', resize);
    let pieces = Array.from({ length: 120 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height - canvas.height,
        r: Math.random() * 360,
        c: colors[Math.floor(Math.random() * colors.length)],
        s: Math.random() * 8 + 4,
        v: Math.random() * 3 + 2,
        o: 1,
    }));
    const animate = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pieces = pieces.filter(p => p.o > 0);
        for (const p of pieces) {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.r * Math.PI / 180);
            ctx.globalAlpha = p.o;
            ctx.fillStyle = p.c;
            ctx.fillRect(-p.s / 2, -p.s / 2, p.s, p.s);
            ctx.restore();
            p.y += p.v;
            p.r += p.v;
            if (p.y > canvas.height) p.o -= 0.03;
        }
        if (pieces.length) requestAnimationFrame(animate);
    };
    setTimeout(animate, 400);
})();
</script>
@endif
</x-marketplace-layout>
