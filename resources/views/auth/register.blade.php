<x-marketplace-layout title="Create account">
    <div class="mx-auto max-w-md px-6 py-20">
        <h1 class="text-4xl font-black">Join DiginMarket</h1>
        <p class="mt-2 text-slate-400">One account for buying and selling.</p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
            @csrf
            @foreach (['name' => 'Name', 'email' => 'Email'] as $field => $label)
                <label class="block">{{ $label }}
                    <input name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 p-3">
                </label>
                @error($field)
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            @endforeach
            <label class="block">Password
                <input name="password" type="password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 p-3">
            </label>
            <p class="text-sm text-slate-500">At least 8 characters with uppercase, lowercase and a number.</p>
            @error('password')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror
            <label class="block">Confirm password
                <input name="password_confirmation" type="password" required class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 p-3">
            </label>
            @error('password_confirmation')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror
            <button class="w-full rounded-xl bg-emerald-400 p-3 font-bold text-slate-950">Create account</button>
        </form>
    </div>
</x-marketplace-layout>
