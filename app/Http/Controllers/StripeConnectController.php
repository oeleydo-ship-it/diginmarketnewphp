<?php
namespace App\Http\Controllers;
use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
class StripeConnectController extends Controller
{
 public function start(StripeConnectService $service):RedirectResponse{return redirect()->away($service->onboarding(auth()->user()));}
 public function returned(StripeConnectService $service):RedirectResponse{$service->sync(auth()->user());return redirect()->route('seller.finance')->with('status','Stripe account status updated.');}
 public function refresh(StripeConnectService $service):RedirectResponse{return redirect()->away($service->onboarding(auth()->user()));}
}