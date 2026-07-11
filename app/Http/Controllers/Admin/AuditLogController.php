<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
class AuditLogController extends Controller
{
 public function index(): View
 {
  $logs=AuditLog::with('user')->when(request('action'),fn($query,$action)=>$query->where('action','like',$action.'%'))->latest('created_at')->paginate(40)->withQueryString();
  return view('admin.audits.index',compact('logs'));
 }
}
