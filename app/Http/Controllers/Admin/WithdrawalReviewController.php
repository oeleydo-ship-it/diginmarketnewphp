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
 public function reject(WithdrawalRequest $withdrawalRequest,WithdrawalService $service):RedirectResponse{$service->reject($withdrawalRequest,(string)request('note'));return back()->with('status','Withdrawal rejected and funds returned.');}
}
