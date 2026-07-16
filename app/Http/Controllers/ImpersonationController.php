<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
class ImpersonationController extends Controller
{
 public const SESSION_KEY='impersonator_id';
 public const EXPIRES_KEY='impersonation_expires_at';
 public const MINUTES=30;
 public function start(User $user): RedirectResponse
 {
  abort_if($user->hasRole('administrator'),403,'Administrators cannot be impersonated.');
  abort_if($user->id===auth()->id(),422,'You are already signed in as this account.');
  abort_if(session()->has(self::SESSION_KEY),422,'Stop the current impersonation first.');
  $admin=auth()->user();
  AuditLog::create(['user_id'=>$admin->id,'action'=>'user.impersonation_started','entity_type'=>User::class,'entity_id'=>$user->id,'new_values'=>['impersonated'=>$user->email,'expires_at'=>now()->addMinutes(self::MINUTES)->toIso8601String()],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  session()->put(self::SESSION_KEY,$admin->id);
  session()->put(self::EXPIRES_KEY,now()->addMinutes(self::MINUTES)->timestamp);
  Auth::login($user);
  session()->regenerate();
  // login() clears freshly regenerated session data on some drivers; re-put to be safe.
  session()->put(self::SESSION_KEY,$admin->id);
  session()->put(self::EXPIRES_KEY,now()->addMinutes(self::MINUTES)->timestamp);
  return redirect()->route('dashboard')->with('status','You are now impersonating '.$user->name.'. The session ends automatically after '.self::MINUTES.' minutes.');
 }
 public function stop(): RedirectResponse
 {
  abort_unless(session()->has(self::SESSION_KEY),404);
  $admin=User::findOrFail(session(self::SESSION_KEY));
  AuditLog::create(['user_id'=>$admin->id,'action'=>'user.impersonation_stopped','entity_type'=>User::class,'entity_id'=>auth()->id(),'new_values'=>['impersonated'=>auth()->user()?->email],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  session()->forget([self::SESSION_KEY,self::EXPIRES_KEY]);
  Auth::login($admin);
  session()->regenerate();
  return redirect()->route('admin.users.index')->with('status','Impersonation ended.');
 }
}
