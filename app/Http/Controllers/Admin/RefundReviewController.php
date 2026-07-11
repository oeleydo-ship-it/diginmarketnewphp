<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
class RefundReviewController extends Controller {public function approve(RefundRequest $refundRequest,RefundService $service):RedirectResponse{$data=request()->validate(['amount'=>['required','numeric','min:0.01'],'decision'=>['nullable','string','max:2000']]);$service->approve($refundRequest->load('orderItem.order.payments','orderItem.license'),auth()->user(),(float)$data['amount'],(string)($data['decision']??''));return back()->with('status','Refund confirmed and fulfilment revoked.');}}