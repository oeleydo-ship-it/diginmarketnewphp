<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }
        $data = $request->validate(['code' => ['nullable', 'string'], 'recovery_code' => ['nullable', 'string']]);
        $user = User::findOrFail($userId);
        $passed = match (true) {
            filled($data['code'] ?? null) => $twoFactor->verify((string) $user->two_factor_secret, $data['code']),
            filled($data['recovery_code'] ?? null) => $user->useRecoveryCode(trim($data['recovery_code'])),
            default => false,
        };
        if (! $passed) {
            throw ValidationException::withMessages(['code' => 'The provided two-factor code was invalid.']);
        }
        // Challenge cleared: complete the deferred login using the remembered choice.
        $remember = (bool) $request->session()->pull('2fa.remember', false);
        $request->session()->forget('2fa.user_id');
        Auth::login($user, $remember);
        $request->session()->regenerate();
        app(AuthenticatedSessionController::class)->recordLogin($request);

        return redirect()->intended(route('dashboard'));
    }
}
