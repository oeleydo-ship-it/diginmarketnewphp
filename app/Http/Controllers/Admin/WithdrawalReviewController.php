<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class WithdrawalReviewController extends Controller
{
 public function index(): View {$withdrawals=WithdrawalRequest::with('wallet')->whereIn('status',['pending','under_review'])->oldest()->paginate(20);return view('admin.withdrawals.index',compact('withdrawals'));}
 public function approve(WithdrawalRequest $withdrawalRequest,WithdrawalService $service):RedirectResponse{$service->approve($withdrawalRequest,auth()->user(),(string)request('note'));return back()->with('status','Withdrawal paid through Stripe transfer.');}
 public function markPaid(WithdrawalRequest $withdrawalRequest,WithdrawalService $service):RedirectResponse{$data=request()->validate(['reference'=>['required','string','max:120'],'note'=>['nullable','string','max:500']]);$service->markPaid($withdrawalRequest,auth()->user(),$data['reference'],(string)($data['note']??''));return back()->with('status','Withdrawal marked as paid via '.$withdrawalRequest->methodLabel().'.');}
 public function reject(WithdrawalRequest $withdrawalRequest,WithdrawalService $service):RedirectResponse{$service->reject($withdrawalRequest,(string)request('note'));return back()->with('status','Withdrawal rejected and funds returned.');}
}
