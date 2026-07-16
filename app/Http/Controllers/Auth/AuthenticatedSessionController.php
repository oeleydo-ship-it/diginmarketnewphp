<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->safe()->only('email', 'password'), $request->boolean('remember'))) throw ValidationException::withMessages(['email' => __('auth.failed')]);
        if (! $request->user()->isActive()) { Auth::logout(); throw ValidationException::withMessages(['email' => 'This account is not active.']); }
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        \App\Models\LoginActivity::create(['user_id' => $request->user()->id, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'logged_in_at' => now()]);
        return redirect()->intended(route('dashboard'));
    }
    public function destroy(): RedirectResponse
    {
        Auth::logout(); request()->session()->invalidate(); request()->session()->regenerateToken();
        return redirect()->route('home');
    }
}
