<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ImpersonationController;
use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonationExpiry
{
    public function handle(Request $request, Closure $next): Response
    {
        $expires = $request->session()->get(ImpersonationController::EXPIRES_KEY);
        if ($expires !== null && now()->timestamp > (int) $expires) {
            $admin = User::find($request->session()->get(ImpersonationController::SESSION_KEY));
            AuditLog::create(['user_id' => $admin?->id, 'action' => 'user.impersonation_expired', 'entity_type' => User::class, 'entity_id' => Auth::id(), 'new_values' => ['impersonated' => Auth::user()?->email], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
            $request->session()->forget([ImpersonationController::SESSION_KEY, ImpersonationController::EXPIRES_KEY]);
            if ($admin) {
                Auth::login($admin);
                $request->session()->regenerate();
                return redirect()->route('admin.users.index')->with('status', 'The impersonation session expired.');
            }
            Auth::logout();
            return redirect()->route('login');
        }
        return $next($request);
    }
}
