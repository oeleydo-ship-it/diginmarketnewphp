<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class UserDirectoryController extends Controller
{
 public function index(): View
 {
  $users=User::with('roles')->when(request('q'),fn($query,$term)=>$query->where(fn($q)=>$q->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%')))->when(request('role'),fn($query,$role)=>$query->whereHas('roles',fn($q)=>$q->where('slug',$role)))->latest()->paginate(25)->withQueryString();
  return view('admin.users.index',compact('users'));
 }
 public function updateStatus(User $user): RedirectResponse
 {
  $data=request()->validate(['status'=>['required','in:active,suspended']]);
  abort_if($user->id===auth()->id(),422,'You cannot change your own status.');
  $old=$user->status;$user->update(['status'=>$data['status']]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'user.status_changed','entity_type'=>User::class,'entity_id'=>$user->id,'old_values'=>['status'=>$old],'new_values'=>['status'=>$data['status']],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','User status updated.');
 }
}
