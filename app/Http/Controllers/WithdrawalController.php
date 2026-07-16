<?php
namespace App\Http\Controllers;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class WithdrawalController extends Controller
{
 public function store(WithdrawalService $service): RedirectResponse
 {
  $profile=auth()->user()->sellerProfile;
  // "use_default" lets the seller withdraw to their saved method with only an amount.
  if(request()->boolean('use_default') && $profile?->hasDefaultPayout()){
   $data=request()->validate(['amount'=>['required','numeric','min:1']]);
   $wallet=auth()->user()->sellerWallets()->where('currency','USD')->firstOrCreate(['currency'=>'USD']);
   $service->request($wallet,(float)$data['amount'],$profile->default_payout_method,$profile->default_payout_details);
   return back()->with('status','Withdrawal requested to your saved payout method and funds reserved.');
  }
  $data=request()->validate([
   'amount'=>['required','numeric','min:1'],
   'payout_method'=>['required',Rule::in(['stripe','paypal','bank'])],
   'paypal_email'=>['required_if:payout_method,paypal','nullable','email','max:255'],
   'bank_name'=>['required_if:payout_method,bank','nullable','string','max:120'],
   'account_name'=>['required_if:payout_method,bank','nullable','string','max:120'],
   'account_number'=>['required_if:payout_method,bank','nullable','string','max:40'],
   'routing_number'=>['nullable','string','max:40'],
   'swift'=>['nullable','string','max:20'],
   'save_default'=>['nullable','boolean'],
  ]);
  $details=SellerPayoutSettingsController::detailsFor($data);
  if(request()->boolean('save_default') && $profile){
   $profile->update(['default_payout_method'=>$data['payout_method'],'default_payout_details'=>$details]);
  }
  $wallet=auth()->user()->sellerWallets()->where('currency','USD')->firstOrCreate(['currency'=>'USD']);
  $service->request($wallet,(float)$data['amount'],$data['payout_method'],$details);
  return back()->with('status','Withdrawal requested and funds reserved.');
 }
}
