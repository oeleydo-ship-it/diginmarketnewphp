<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->safe()->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }
        if (! $request->user()->isActive()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account is not active.']);
        }
        // With 2FA on, credentials are not enough: drop the session back to guest and hand off to
        // the code challenge, remembering the intended target and the remember-me choice.
        if ($request->user()->hasTwoFactorEnabled()) {
            $userId = $request->user()->id;
            Auth::logout();
            $request->session()->put('2fa.user_id', $userId);
            $request->session()->put('2fa.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }
        $request->session()->regenerate();
        $this->recordLogin($request);

        return redirect()->intended(route('dashboard'));
    }

    /** Shared post-authentication bookkeeping for both the direct and 2FA-verified paths. */
    public function recordLogin(Request $request): void
    {
        $request->user()->forceFill(['last_login_at' => now()])->save();
        LoginActivity::create(['user_id' => $request->user()->id, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'logged_in_at' => now()]);
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
