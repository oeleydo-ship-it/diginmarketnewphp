<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
class AuditLogController extends Controller
{
 public function index(): View
 {
  $logs=$this->filtered()->with('user')->latest('created_at')->paginate(40)->withQueryString();
  $administrators=User::whereHas('roles',fn(Builder $query)=>$query->where('slug','administrator'))->orderBy('name')->get(['id','name']);
  return view('admin.audits.index',compact('logs','administrators'));
 }

 public function export(): StreamedResponse
 {
  $logs=$this->filtered()->with('user')->latest('created_at')->get();
  return response()->streamDownload(function() use ($logs): void {
   $handle=fopen('php://output','w');
   fputcsv($handle,['Date','Action','Administrator','Entity type','Entity ID','IP address','Changes']);
   foreach($logs as $log) fputcsv($handle,[$log->created_at->toDateTimeString(),$log->action,$log->user?->name ?? 'System',class_basename((string)$log->entity_type),$log->entity_id,$log->ip_address,json_encode($log->new_values)]);
   fclose($handle);
  },'audit-log-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv']);
 }

 private function filtered(): Builder
 {
  return AuditLog::query()
   ->when(request('action'),fn(Builder $query,string $action)=>$query->where('action','like','%'.$action.'%'))
   ->when(request('user'),fn(Builder $query,string $user)=>$query->where('user_id',$user))
   ->when(request('entity'),fn(Builder $query,string $entity)=>$query->where('entity_type','like','%\\'.$entity))
   ->when(request('from'),fn(Builder $query,string $from)=>$query->whereDate('created_at','>=',$from))
   ->when(request('to'),fn(Builder $query,string $to)=>$query->whereDate('created_at','<=',$to));
 }
}
