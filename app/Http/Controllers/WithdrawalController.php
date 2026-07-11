<?php
namespace App\Http\Controllers;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
class WithdrawalController extends Controller {public function store(WithdrawalService $service):RedirectResponse{$data=request()->validate(['amount'=>['required','numeric','min:1']]);$wallet=auth()->user()->sellerWallets()->where('currency','USD')->firstOrCreate(['currency'=>'USD']);$service->request($wallet,(float)$data['amount']);return back()->with('status','Withdrawal requested and funds reserved.');}}