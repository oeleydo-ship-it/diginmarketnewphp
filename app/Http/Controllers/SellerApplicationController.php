<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Http\Requests\StoreSellerApplicationRequest;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
class SellerApplicationController extends Controller
{
 public function create(): View { abort_unless(\App\Models\Setting::enabled('features.seller_applications'),403,'Seller applications are currently closed.');abort_if(request()->user()->sellerProfile,409);return view('seller.apply'); }
 public function store(StoreSellerApplicationRequest $request): RedirectResponse { abort_unless(\App\Models\Setting::enabled('features.seller_applications'),403,'Seller applications are currently closed.');$request->user()->sellerProfile()->create($request->validated()+['status'=>SellerStatus::Pending]);return redirect()->route('dashboard')->with('status','Seller application submitted for review.'); }
}