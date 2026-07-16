<?php
namespace App\Http\Controllers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class SellerPayoutSettingsController extends Controller
{
 public function update(): RedirectResponse
 {
  $data=request()->validate([
   'payout_method'=>['required',Rule::in(\App\Support\PayoutMethods::enabled())],
   'paypal_email'=>['required_if:payout_method,paypal','nullable','email','max:255'],
   'bank_name'=>['required_if:payout_method,bank','nullable','string','max:120'],
   'account_name'=>['required_if:payout_method,bank','nullable','string','max:120'],
   'account_number'=>['required_if:payout_method,bank','nullable','string','max:40'],
   'routing_number'=>['nullable','string','max:40'],
   'swift'=>['nullable','string','max:20'],
  ]);
  $details=self::detailsFor($data);
  $profile=auth()->user()->sellerProfile;
  abort_unless($profile,404);
  $profile->update(['default_payout_method'=>$data['payout_method'],'default_payout_details'=>$details]);
  return back()->with('status','Default payout method saved.');
 }
 /** Shared with WithdrawalController: builds the payout_details payload from validated input. */
 public static function detailsFor(array $data): ?array
 {
  return match($data['payout_method']){
   'paypal'=>['email'=>$data['paypal_email']],
   'bank'=>array_filter(['bank_name'=>$data['bank_name'],'account_name'=>$data['account_name'],'account_number'=>$data['account_number'],'routing_number'=>$data['routing_number']??null,'swift'=>$data['swift']??null]),
   default=>null,
  };
 }
}
