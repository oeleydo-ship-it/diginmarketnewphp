<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request): RedirectResponse
    {
        abort_unless(Setting::enabled('features.registration'), 403, 'Registration is currently disabled.');
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->safe()->only('name', 'email', 'password'));
            $user->roles()->attach(Role::where('slug', 'customer')->firstOrFail());

            return $user;
        });
        // Fires the framework's SendEmailVerificationNotification listener.
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
