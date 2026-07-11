<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
class WithdrawalReviewController extends Controller {public function reject(WithdrawalRequest $withdrawalRequest,WithdrawalService $service):RedirectResponse{$service->reject($withdrawalRequest,(string)request('note'));return back()->with('status','Withdrawal rejected and funds returned.');}}