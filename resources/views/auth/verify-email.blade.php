<x-marketplace-layout title="Verify your email — DiginMarket">
<div class="flex min-h-[70vh] items-center justify-center px-6 py-16">
    <div class="w-full max-w-md rounded-2xl border border-outline-variant bg-surface-container-lowest p-8 text-center shadow-sm">
        <span class="material-symbols-outlined text-5xl text-primary">mark_email_unread</span>
        <h1 class="mt-4 font-display text-2xl font-bold">Verify your email address</h1>
        <p class="mt-2 text-sm text-on-surface-variant">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. You need to verify before checking out or applying to sell.</p>
        @if(session('status') === 'verification-link-sent')
            <div class="mt-5 rounded-lg border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container">A fresh verification link is on its way.</div>
        @endif
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button class="text-sm font-semibold text-on-surface-variant hover:text-primary">Sign out</button>
        </form>
    </div>
</div>
</x-marketplace-layout>
