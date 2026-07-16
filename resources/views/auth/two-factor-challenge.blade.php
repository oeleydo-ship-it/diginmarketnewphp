<x-marketplace-layout title="Two-Factor Verification — DiginMarket">
<div class="mx-auto max-w-md px-6 py-20">
    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        <div class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary-container/30">
            <span class="material-symbols-outlined text-[26px] text-primary" aria-hidden="true">encrypted</span>
        </div>
        <h1 class="font-display text-3xl font-semibold tracking-tight">Two-factor verification</h1>
        <p class="mt-2 text-sm text-on-surface-variant">Enter the 6-digit code from your authenticator app to finish signing in.</p>
        <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-8 space-y-5">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="code" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Authentication code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus
                    class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-center font-mono text-lg tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-primary/20">
                @error('code')<p class="text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Verify</button>
        </form>
        <details class="mt-6">
            <summary class="cursor-pointer text-sm font-semibold text-on-surface-variant hover:text-primary">Lost your device? Use a recovery code</summary>
            <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-4 space-y-4">
                @csrf
                <input name="recovery_code" placeholder="xxxxx-xxxxx" autocomplete="off"
                    class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-center font-mono text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                <button class="w-full rounded-xl border border-outline-variant p-3 text-sm font-semibold transition-colors hover:border-primary hover:text-primary">Use recovery code</button>
            </form>
        </details>
    </div>
</div>
</x-marketplace-layout>
