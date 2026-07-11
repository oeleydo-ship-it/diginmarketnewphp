<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User { $user = User::create($request->safe()->only('name', 'email', 'password')); $user->roles()->attach(Role::where('slug', 'customer')->firstOrFail()); return $user; });
        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
