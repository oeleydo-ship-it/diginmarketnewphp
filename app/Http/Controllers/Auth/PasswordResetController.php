<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(): RedirectResponse
    {
        $data = request()->validate(['email' => ['required', 'email']]);
        // Always report success so the form can't be used to probe which emails exist.
        Password::sendResetLink($data);

        return back()->with('status', 'If that email belongs to an account, a reset link is on its way.');
    }

    public function reset(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) request('email')]);
    }

    public function update(): RedirectResponse
    {
        $data = request()->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);
        $status = Password::reset($data, function ($user, string $password): void {
            $user->forceFill(['password' => bcrypt($password), 'remember_token' => str()->random(60)])->save();
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with the new password.');
    }
}
