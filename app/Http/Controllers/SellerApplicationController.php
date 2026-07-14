<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Http\Requests\StoreSellerApplicationRequest;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
class SellerApplicationController extends Controller
{
 public function create(): View|RedirectResponse
 {
  abort_unless(\App\Models\Setting::enabled('features.seller_applications'),403,'Seller applications are currently closed.');
  if($profile=request()->user()->sellerProfile){
   if($profile->status===SellerStatus::Approved)return redirect()->route('seller.products.index');
   return redirect()->route('dashboard')->with('status',$profile->status===SellerStatus::Pending?'Your seller application is already under review.':'You have already applied to sell. Current application status: '.$profile->status->value.'.');
  }
  return view('seller.apply');
 }
 public function store(StoreSellerApplicationRequest $request): RedirectResponse { abort_unless(\App\Models\Setting::enabled('features.seller_applications'),403,'Seller applications are currently closed.');$request->user()->sellerProfile()->create($request->validated()+['status'=>SellerStatus::Pending]);return redirect()->route('dashboard')->with('status','Seller application submitted for review.'); }
}