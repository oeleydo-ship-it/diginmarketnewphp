<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        // A pending (unconfirmed) secret means enrolment is mid-flow: surface the QR/key and code prompt.
        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return view('account.security', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'pending' => $pending,
            'provisioningUri' => $pending ? $this->twoFactor->provisioningUri($user, (string) $user->two_factor_secret) : null,
            'secret' => $pending ? (string) $user->two_factor_secret : null,
            'recoveryCodes' => $pending ? [] : ($request->session()->get('2fa.recovery_codes', [])),
        ]);
    }

    /** Step 1: stage a secret and recovery codes. Nothing is enforced until the user confirms. */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasTwoFactorEnabled(), 422, 'Two-factor authentication is already enabled.');
        $user->forceFill([
            'two_factor_secret' => $this->twoFactor->generateSecret(),
            'two_factor_recovery_codes' => $this->twoFactor->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return back();
    }

    /** Step 2: prove possession of the authenticator before 2FA takes effect. */
    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->two_factor_secret === null, 422, 'Start two-factor setup first.');
        $data = $request->validate(['code' => ['required', 'string']]);
        if (! $this->twoFactor->verify((string) $user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code did not match. Check your authenticator app and try again.']);
        }
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        AuditLog::create(['user_id' => $user->id, 'action' => 'two_factor.enabled', 'entity_type' => User::class, 'entity_id' => $user->id, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        // Show the recovery codes exactly once, right after enrolment.
        return redirect()->route('account.security')->with('2fa.recovery_codes', (array) $user->two_factor_recovery_codes)->with('status', 'Two-factor authentication is now on.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        AuditLog::create(['user_id' => $user->id, 'action' => 'two_factor.disabled', 'entity_type' => User::class, 'entity_id' => $user->id, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Two-factor authentication has been turned off.');
    }

    public function recoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 422, 'Enable two-factor authentication first.');
        $user->forceFill(['two_factor_recovery_codes' => $this->twoFactor->generateRecoveryCodes()])->save();

        return redirect()->route('account.security')->with('2fa.recovery_codes', (array) $user->two_factor_recovery_codes)->with('status', 'New recovery codes generated.');
    }
}
