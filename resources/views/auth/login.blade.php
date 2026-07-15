<x-marketplace-layout title="Sign in — DiginMarket">
<div class="mx-auto max-w-md px-6 py-20">
    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        <h1 class="font-display text-3xl font-semibold tracking-tight">Welcome back</h1>
        <p class="mt-2 text-sm text-on-surface-variant">Sign in to purchases, products, and earnings.</p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="email" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Email Address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                @error('email')<p class="text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="password" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Password</label>
                <input id="password" name="password" type="password" required
                    class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                <input name="remember" type="checkbox" value="1" class="rounded border-outline-variant text-primary focus:ring-primary/20">
                Remember me
            </label>
            <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Sign in</button>
        </form>
        <p class="mt-6 text-center text-sm text-on-surface-variant">
            @if(\App\Models\Setting::enabled('features.registration'))New to DiginMarket? <a href="{{ route('register') }}" class="font-semibold text-primary hover:underline">Create an account</a>@endif
        </p>
    </div>
</div>

@if(session('registration_closed'))
    <div data-modal class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div data-modal-dismiss class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div role="alertdialog" aria-modal="true" aria-labelledby="reg-modal-title" class="relative w-full max-w-md rounded-2xl border border-outline-variant bg-surface-container-lowest p-8 text-center shadow-2xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-container/30">
                <span class="material-symbols-outlined text-[32px] text-primary" aria-hidden="true">person_off</span>
            </div>
            <h2 id="reg-modal-title" class="font-display text-xl font-semibold tracking-tight">Registration unavailable</h2>
            <p class="mt-2 text-sm text-on-surface-variant">New account registration is currently disabled. We’re sorry for the inconvenience — please check back soon or sign in if you already have an account.</p>
            <button type="button" data-modal-dismiss class="mt-6 w-full rounded-xl bg-primary p-3 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Got it</button>
        </div>
    </div>
@endif
</x-marketplace-layout>
