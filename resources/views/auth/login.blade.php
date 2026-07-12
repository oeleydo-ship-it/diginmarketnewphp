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
            New to DiginMarket? <a href="{{ route('register') }}" class="font-semibold text-primary hover:underline">Create an account</a>
        </p>
    </div>
</div>
</x-marketplace-layout>
