<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
class SupportManagementController extends Controller
{
 public function index(): View
 {
  $tickets=SupportTicket::with(['customer','seller','product'])->withCount('messages')
   ->when(request('q'),fn(Builder $query,string $term)=>$query->where(fn(Builder $query)=>$query->where('number','like','%'.$term.'%')->orWhere('subject','like','%'.$term.'%')->orWhereHas('customer',fn(Builder $customer)=>$customer->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%'))))
   ->when(request('status'),fn(Builder $query,string $status)=>$query->where('status',$status))
   ->when(request('priority'),fn(Builder $query,string $priority)=>$query->where('priority',$priority))
   ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")->latest()->paginate(25)->withQueryString();
  $counts=['open'=>SupportTicket::whereIn('status',['open','awaiting_seller','awaiting_customer'])->count(),'urgent'=>SupportTicket::where('priority','urgent')->whereNotIn('status',['resolved','closed'])->count(),'unanswered'=>SupportTicket::whereNull('first_response_at')->whereNotIn('status',['resolved','closed'])->count(),'resolved'=>SupportTicket::where('status','resolved')->count()];
  return view('admin.support.index',compact('tickets','counts'));
 }
 public function show(SupportTicket $supportTicket): View
 {
  $supportTicket->load(['customer','seller','product','order','messages.user']);
  return view('admin.support.show',['ticket'=>$supportTicket]);
 }
 public function reply(SupportTicket $supportTicket): RedirectResponse
 {
  $data=request()->validate(['message'=>['required','string','min:2','max:5000'],'is_internal'=>['nullable','boolean']]);
  $supportTicket->messages()->create(['user_id'=>auth()->id(),'message'=>$data['message'],'is_internal'=>(bool)($data['is_internal']??false)]);
  if(!($data['is_internal']??false))$supportTicket->update(['first_response_at'=>$supportTicket->first_response_at??now(),'status'=>'awaiting_customer']);
  $this->audit($supportTicket,'support.replied',['internal'=>(bool)($data['is_internal']??false)]);
  return back()->with('status',($data['is_internal']??false)?'Internal note added.':'Reply sent.');
 }
 public function updateStatus(SupportTicket $supportTicket): RedirectResponse
 {
  $data=request()->validate(['status'=>['required','in:open,awaiting_seller,awaiting_customer,resolved,closed'],'priority'=>['nullable','in:low,normal,high,urgent']]);
  $old=['status'=>$supportTicket->status,'priority'=>$supportTicket->priority];
  $supportTicket->update(['status'=>$data['status'],'priority'=>$data['priority']??$supportTicket->priority,'resolved_at'=>$data['status']==='resolved'?now():null]);
  $this->audit($supportTicket,'support.status_changed',['old'=>$old,'new'=>['status'=>$supportTicket->status,'priority'=>$supportTicket->priority]]);
  return back()->with('status','Ticket updated.');
 }
 private function audit(SupportTicket $ticket,string $action,array $values): void
 {
  AuditLog::create(['user_id'=>auth()->id(),'action'=>$action,'entity_type'=>SupportTicket::class,'entity_id'=>$ticket->id,'new_values'=>$values,'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
 }
}
