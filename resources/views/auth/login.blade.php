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
            <p class="text-right text-sm"><a href="{{ route('password.request') }}" class="font-semibold text-primary hover:underline">Forgot password?</a></p>
        </form>
        @php($googleOn = \App\Models\Setting::enabled('auth.google.enabled', false) && config('services.google.client_id'))
        @php($facebookOn = \App\Models\Setting::enabled('auth.facebook.enabled', false) && config('services.facebook.client_id'))
        @if($googleOn || $facebookOn)
            <div class="mt-5 flex items-center gap-3 text-xs uppercase tracking-wide text-on-surface-variant"><span class="h-px flex-1 bg-outline-variant"></span>or<span class="h-px flex-1 bg-outline-variant"></span></div>
        @endif
        @if($facebookOn)
            <a href="{{ route('auth.facebook.redirect') }}" class="mt-5 flex w-full items-center justify-center gap-3 rounded-xl border border-outline-variant bg-surface p-3.5 font-semibold text-on-surface transition-all hover:bg-surface-container active:scale-95">
                <svg viewBox="0 0 24 24" class="h-5 w-5" aria-hidden="true"><path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.89v2.26h3.32l-.53 3.49h-2.79V24C19.61 23.09 24 18.1 24 12.07z"/></svg>
                Continue with Facebook
            </a>
        @endif
        @if($googleOn)
            <a href="{{ route('auth.google.redirect') }}" class="mt-5 flex w-full items-center justify-center gap-3 rounded-xl border border-outline-variant bg-surface p-3.5 font-semibold text-on-surface transition-all hover:bg-surface-container active:scale-95">
                <svg viewBox="0 0 24 24" class="h-5 w-5" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A11 11 0 0 0 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                Continue with Google
            </a>
        @endif
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
