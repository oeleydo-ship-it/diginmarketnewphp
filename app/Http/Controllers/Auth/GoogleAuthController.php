<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Google OAuth 2.0 sign-in implemented directly on the HTTP client (the authorization-code
 * flow is three requests; no SDK needed). State is a one-shot session nonce against CSRF.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless($this->enabled(), 404);
        $state = str()->random(40);
        session()->put('google_oauth_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => (string) config('services.google.client_id'),
            'redirect_uri' => route('auth.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(): RedirectResponse
    {
        abort_unless($this->enabled(), 404);
        $state = (string) session()->pull('google_oauth_state');
        if ($state === '' || ! hash_equals($state, (string) request('state'))) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in was cancelled or the request expired. Please try again.']);
        }
        if (! request('code')) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in was cancelled.']);
        }
        $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => (string) config('services.google.client_id'),
            'client_secret' => (string) config('services.google.secret'),
            'code' => (string) request('code'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('auth.google.callback'),
        ]);
        if ($token->failed() || ! $token->json('access_token')) {
            return redirect()->route('login')->withErrors(['email' => 'Google did not confirm the sign-in. Please try again.']);
        }
        $profile = Http::withToken((string) $token->json('access_token'))->get('https://openidconnect.googleapis.com/v1/userinfo');
        $googleId = (string) $profile->json('sub');
        $email = strtolower((string) $profile->json('email'));
        if ($profile->failed() || $googleId === '' || $email === '' || ! $profile->json('email_verified')) {
            return redirect()->route('login')->withErrors(['email' => 'Google did not return a verified email address.']);
        }

        // Match by google_id first, then by verified email (links Google to an existing account).
        $user = User::where('google_id', $googleId)->first() ?? User::where('email', $email)->first();
        if (! $user) {
            abort_unless(Setting::enabled('features.registration'), 403, 'Registration is currently closed.');
            $user = User::create(['name' => (string) ($profile->json('name') ?: str($email)->before('@')), 'email' => $email, 'password' => bcrypt(str()->random(40)), 'status' => 'active']);
            $user->roles()->syncWithoutDetaching([Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer'])->id]);
        }
        if ($user->google_id !== $googleId) {
            $user->forceFill(['google_id' => $googleId])->save();
        }
        // Google verified the address; local verification would be redundant friction.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        if (! $user->isActive()) {
            return redirect()->route('login')->withErrors(['email' => 'This account is not active.']);
        }
        // Same handoff as password login: 2FA-enabled accounts still face the code challenge.
        if ($user->hasTwoFactorEnabled()) {
            session()->put('2fa.user_id', $user->id);
            session()->put('2fa.remember', true);

            return redirect()->route('two-factor.challenge');
        }
        Auth::login($user, remember: true);
        session()->regenerate();
        app(AuthenticatedSessionController::class)->recordLogin(request());

        return redirect()->intended(route('dashboard'));
    }

    private function enabled(): bool
    {
        return Setting::enabled('auth.google.enabled', false) && config('services.google.client_id') && config('services.google.secret');
    }
}
