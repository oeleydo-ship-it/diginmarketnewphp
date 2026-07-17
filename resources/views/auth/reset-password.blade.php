<x-marketplace-layout title="Choose a new password — DiginMarket">
<div class="flex min-h-[70vh] items-center justify-center px-6 py-16">
    <div class="w-full max-w-md rounded-2xl border border-outline-variant bg-surface-container-lowest p-8 shadow-sm">
        <h1 class="font-display text-2xl font-bold">Choose a new password</h1>
        @if($errors->any())<div class="mt-5 rounded-lg border border-error/30 bg-error-container/40 px-4 py-3 text-sm font-medium text-on-error-container">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Email address</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $email) }}" class="w-full rounded-xl border border-outline-variant bg-surface p-3.5 focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">New password (min 10 characters)</label>
                <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password" class="w-full rounded-xl border border-outline-variant bg-surface p-3.5 focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" autocomplete="new-password" class="w-full rounded-xl border border-outline-variant bg-surface p-3.5 focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <button class="w-full rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95">Reset password</button>
        </form>
    </div>
</div>
</x-marketplace-layout>
