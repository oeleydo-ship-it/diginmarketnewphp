<?php
namespace App\Http\Controllers;
use App\Models\AffiliateProfile;
use App\Services\AffiliateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class AffiliateController extends Controller
{
 public function show(): View
 {
  $profile=AffiliateProfile::where('user_id',auth()->id())->first();
  $earnings=$profile?$profile->earnings()->with('order')->latest()->paginate(15):null;
  return view('affiliates.show',compact('profile','earnings'));
 }
 public function store(AffiliateService $service): RedirectResponse
 {
  $service->enroll(auth()->user());
  return back()->with('status','Welcome to the affiliate program! Your referral link is ready.');
 }
}
