<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Services\SellerApprovalService;
use Illuminate\Http\RedirectResponse;
class SellerReviewController extends Controller
{
 public function approve(SellerProfile $sellerProfile,SellerApprovalService $service): RedirectResponse { abort_unless(auth()->user()->hasRole('administrator'),403);$service->approve($sellerProfile,auth()->user());return back()->with('status','Seller approved.'); }
}