<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/** Facebook OAuth sign-in via the Graph API — same shape as the Google controller. */
class FacebookAuthController extends Controller
{
    private const GRAPH = 'https://graph.facebook.com/v19.0';

    public function redirect(): RedirectResponse
    {
        abort_unless($this->enabled(), 404);
        $state = str()->random(40);
        session()->put('facebook_oauth_state', $state);

        return redirect()->away('https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query([
            'client_id' => (string) config('services.facebook.client_id'),
            'redirect_uri' => route('auth.facebook.callback'),
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state,
        ]));
    }

    public function callback(): RedirectResponse
    {
        abort_unless($this->enabled(), 404);
        $state = (string) session()->pull('facebook_oauth_state');
        if ($state === '' || ! hash_equals($state, (string) request('state')) || ! request('code')) {
            return redirect()->route('login')->withErrors(['email' => 'Facebook sign-in was cancelled or the request expired. Please try again.']);
        }
        $token = Http::get(self::GRAPH.'/oauth/access_token', [
            'client_id' => (string) config('services.facebook.client_id'),
            'client_secret' => (string) config('services.facebook.secret'),
            'redirect_uri' => route('auth.facebook.callback'),
            'code' => (string) request('code'),
        ]);
        if ($token->failed() || ! $token->json('access_token')) {
            return redirect()->route('login')->withErrors(['email' => 'Facebook did not confirm the sign-in. Please try again.']);
        }
        $profile = Http::get(self::GRAPH.'/me', ['fields' => 'id,name,email', 'access_token' => (string) $token->json('access_token')]);
        $facebookId = (string) $profile->json('id');
        $email = strtolower((string) $profile->json('email'));
        if ($profile->failed() || $facebookId === '' || $email === '') {
            // Facebook only shares the email when the user grants it — without one we can't link safely.
            return redirect()->route('login')->withErrors(['email' => 'Facebook did not share an email address for your account. Please sign in another way.']);
        }

        $user = User::where('facebook_id', $facebookId)->first() ?? User::where('email', $email)->first();
        if (! $user) {
            abort_unless(Setting::enabled('features.registration'), 403, 'Registration is currently closed.');
            $user = User::create(['name' => (string) ($profile->json('name') ?: str($email)->before('@')), 'email' => $email, 'password' => bcrypt(str()->random(40)), 'status' => 'active']);
            $user->roles()->syncWithoutDetaching([Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer'])->id]);
        }
        if ($user->facebook_id !== $facebookId) {
            $user->forceFill(['facebook_id' => $facebookId])->save();
        }
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        if (! $user->isActive()) {
            return redirect()->route('login')->withErrors(['email' => 'This account is not active.']);
        }
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
        return Setting::enabled('auth.facebook.enabled', false) && config('services.facebook.client_id') && config('services.facebook.secret');
    }
}
