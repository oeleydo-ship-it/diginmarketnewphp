<x-marketplace-layout title="Security — DiginMarket">
<div class="mx-auto max-w-2xl px-6 py-16">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-on-surface-variant hover:text-primary">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to dashboard
    </a>
    <h1 class="mt-4 font-display text-3xl font-semibold tracking-tight">Security</h1>
    <p class="mt-2 text-sm text-on-surface-variant">Add a second step at sign-in so a stolen password alone can’t open your account.</p>

    @if(session('status'))
        <div class="mt-6 flex items-center gap-3 rounded-xl border border-secondary-fixed-dim/60 bg-secondary-container/20 px-4 py-3 text-sm font-medium text-on-secondary-container">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>{{ session('status') }}
        </div>
    @endif

    <div class="mt-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 font-display text-lg font-semibold">
                    Two-factor authentication
                    @if($enabled)<span class="rounded-full bg-secondary-container/40 px-2.5 py-0.5 text-xs font-semibold text-on-secondary-container">On</span>
                    @else<span class="rounded-full bg-surface-container px-2.5 py-0.5 text-xs font-semibold text-on-surface-variant">Off</span>@endif
                </h2>
                <p class="mt-1 text-sm text-on-surface-variant">Use an app like Google Authenticator, Authy, or 1Password.</p>
            </div>
            @if(! $enabled && ! $pending)
                <form method="POST" action="{{ route('two-factor.enable') }}">@csrf
                    <button class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Enable</button>
                </form>
            @elseif($enabled)
                <form method="POST" action="{{ route('two-factor.disable') }}" data-confirm="Turn off two-factor authentication?">@csrf @method('DELETE')
                    <button class="rounded-xl border border-error/40 px-5 py-2.5 text-sm font-semibold text-error transition-colors hover:bg-error/5">Turn off</button>
                </form>
            @endif
        </div>

        {{-- Enrolment in progress: show the setup key + otpauth link and ask for a confirming code. --}}
        @if($pending)
            <div class="mt-8 border-t border-outline-variant pt-8">
                <ol class="space-y-6 text-sm">
                    <li>
                        <p class="font-semibold">1. Add this key to your authenticator app</p>
                        <p class="mt-1 text-on-surface-variant">Choose “Enter a setup key” and paste the key below (account: your email).</p>
                        <div class="mt-3 flex items-center gap-3 rounded-lg border border-outline-variant bg-surface-container p-4">
                            <code class="flex-1 break-all font-mono text-sm tracking-wide">{{ trim(chunk_split($secret, 4, ' ')) }}</code>
                        </div>
                        <a href="{{ $provisioningUri }}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                            <span class="material-symbols-outlined text-[16px]">open_in_new</span> Open in an installed app
                        </a>
                    </li>
                    <li>
                        <p class="font-semibold">2. Enter the 6-digit code it shows</p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3 flex flex-wrap items-start gap-3">@csrf
                            <div class="flex flex-col gap-1.5">
                                <input name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="000000"
                                    class="w-40 rounded-lg border border-outline-variant bg-surface p-3 text-center font-mono text-lg tracking-[0.3em] focus:outline-none focus:ring-2 focus:ring-primary/20">
                                @error('code')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>
                            <button class="rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Confirm & activate</button>
                        </form>
                    </li>
                </ol>
                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-6">@csrf @method('DELETE')
                    <button class="text-xs font-semibold text-on-surface-variant hover:text-error">Cancel setup</button>
                </form>
            </div>
        @endif

        {{-- Recovery codes are shown once, right after confirming or regenerating. --}}
        @if(! empty($recoveryCodes))
            <div class="mt-8 rounded-xl border border-tertiary-fixed-dim/50 bg-tertiary-fixed/10 p-6">
                <h3 class="flex items-center gap-2 font-semibold"><span class="material-symbols-outlined text-[20px]">key</span> Recovery codes</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Store these somewhere safe. Each works once if you lose your device. They won’t be shown again.</p>
                <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm">
                    @foreach($recoveryCodes as $code)<div class="rounded bg-surface-container px-3 py-2">{{ $code }}</div>@endforeach
                </div>
            </div>
        @endif

        @if($enabled)
            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-6 border-t border-outline-variant pt-6">@csrf
                <button class="text-sm font-semibold text-primary hover:underline">Regenerate recovery codes</button>
            </form>
        @endif
    </div>
</div>
</x-marketplace-layout>
