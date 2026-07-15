<?php
namespace App\Http\Controllers;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class WithdrawalController extends Controller
{
 public function store(WithdrawalService $service): RedirectResponse
 {
  $data=request()->validate([
   'amount'=>['required','numeric','min:1'],
   'payout_method'=>['required',Rule::in(['stripe','paypal','bank'])],
   'paypal_email'=>['required_if:payout_method,paypal','nullable','email','max:255'],
   'bank_name'=>['required_if:payout_method,bank','nullable','string','max:120'],
   'account_name'=>['required_if:payout_method,bank','nullable','string','max:120'],
   'account_number'=>['required_if:payout_method,bank','nullable','string','max:40'],
   'routing_number'=>['nullable','string','max:40'],
   'swift'=>['nullable','string','max:20'],
  ]);
  $details=match($data['payout_method']){
   'paypal'=>['email'=>$data['paypal_email']],
   'bank'=>array_filter(['bank_name'=>$data['bank_name'],'account_name'=>$data['account_name'],'account_number'=>$data['account_number'],'routing_number'=>$data['routing_number']??null,'swift'=>$data['swift']??null]),
   default=>null,
  };
  $wallet=auth()->user()->sellerWallets()->where('currency','USD')->firstOrCreate(['currency'=>'USD']);
  $service->request($wallet,(float)$data['amount'],$data['payout_method'],$details);
  return back()->with('status','Withdrawal requested and funds reserved.');
 }
}
