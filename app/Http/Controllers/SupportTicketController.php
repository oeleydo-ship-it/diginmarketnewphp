<?php
namespace App\Http\Controllers;
use App\Models\License;
use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class SupportTicketController extends Controller
{
 public function index():View{$tickets=auth()->user()->supportTickets()->latest()->paginate(20);return view('support.index',compact('tickets'));}
 public function store(License $license,SupportService $service):RedirectResponse{$data=request()->validate(['subject'=>['required','string','max:180'],'message'=>['required','string','min:10','max:5000'],'priority'=>['nullable','in:low,normal,high,urgent']]);$ticket=$service->open(auth()->user(),$license->load('orderItem.order'),$data);return redirect()->route('support.show',$ticket);}
 public function show(SupportTicket $supportTicket):View{abort_unless($supportTicket->user_id===auth()->id()||$supportTicket->seller_id===auth()->id()||auth()->user()->hasRole('administrator'),403);$supportTicket->load('messages');return view('support.show',['ticket'=>$supportTicket]);}
 public function reply(SupportTicket $supportTicket):RedirectResponse{abort_unless($supportTicket->user_id===auth()->id()||$supportTicket->seller_id===auth()->id()||auth()->user()->hasRole('administrator'),403);$data=request()->validate(['message'=>['required','string','min:2','max:5000']]);$supportTicket->messages()->create(['user_id'=>auth()->id(),'message'=>$data['message']]);if(auth()->id()===$supportTicket->seller_id&&!$supportTicket->first_response_at)$supportTicket->update(['first_response_at'=>now(),'status'=>'awaiting_customer']);return back();}
}