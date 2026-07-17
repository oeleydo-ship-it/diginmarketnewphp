<x-marketplace-layout title="Reset password — DiginMarket">
<div class="flex min-h-[70vh] items-center justify-center px-6 py-16">
    <div class="w-full max-w-md rounded-2xl border border-outline-variant bg-surface-container-lowest p-8 shadow-sm">
        <h1 class="font-display text-2xl font-bold">Forgot your password?</h1>
        <p class="mt-2 text-sm text-on-surface-variant">Enter your account email and we'll send a link to choose a new one.</p>
        @if(session('status'))<div class="mt-5 rounded-lg border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container">{{ session('status') }}</div>@endif
        @error('email')<div class="mt-5 rounded-lg border border-error/30 bg-error-container/40 px-4 py-3 text-sm font-medium text-on-error-container">{{ $message }}</div>@enderror
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Email address</label>
                <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}" class="w-full rounded-xl border border-outline-variant bg-surface p-3.5 focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Email reset link</button>
        </form>
        <p class="mt-6 text-center text-sm text-on-surface-variant"><a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">Back to sign in</a></p>
    </div>
</div>
</x-marketplace-layout>
