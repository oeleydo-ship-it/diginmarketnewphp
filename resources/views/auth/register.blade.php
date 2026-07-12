<x-marketplace-layout title="Create account — DiginMarket">
    <div class="mx-auto max-w-md px-6 py-20">
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
            <h1 class="font-display text-3xl font-semibold tracking-tight">Join DiginMarket</h1>
            <p class="mt-2 text-sm text-on-surface-variant">One account for buying and selling.</p>
            <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                @csrf
                @foreach (['name' => 'Name', 'email' => 'Email Address'] as $field => $label)
                    <div class="flex flex-col gap-1.5">
                        <label for="{{ $field }}" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field) }}" required
                            class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                        @error($field)<p class="text-sm text-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Password</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <p class="text-xs text-on-surface-variant">At least 8 characters with uppercase, lowercase and a number.</p>
                    @error('password')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        class="w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('password_confirmation')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </div>
                <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Create account</button>
            </form>
            <p class="mt-6 text-center text-sm text-on-surface-variant">
                Already have an account? <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</x-marketplace-layout>
