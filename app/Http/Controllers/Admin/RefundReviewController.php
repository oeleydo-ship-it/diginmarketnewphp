<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\RefundService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class RefundReviewController extends Controller
{
 // 'processing' rows are mid-flight at the payment provider; they stay visible so a claim abandoned
 // by a crashed request is noticed and reconciled by hand instead of vanishing from the queue.
 public function index(): View {$refunds=RefundRequest::with(['user','orderItem'])->whereIn('status',['submitted','under_review','processing'])->oldest()->paginate(20);return view('admin.refunds.index',compact('refunds'));}
 public function approve(RefundRequest $refundRequest,RefundService $service):RedirectResponse{$data=request()->validate(['amount'=>['required','numeric','min:0.01'],'decision'=>['nullable','string','max:2000']]);$service->approve($refundRequest->load('orderItem.order.payments','orderItem.license'),auth()->user(),(float)$data['amount'],(string)($data['decision']??''));return back()->with('status','Refund confirmed and fulfilment revoked.');}
 public function reject(RefundRequest $refundRequest,RefundService $service):RedirectResponse{$service->reject($refundRequest,auth()->user(),(string)request('decision'));return back()->with('status','Refund request rejected.');}
}
