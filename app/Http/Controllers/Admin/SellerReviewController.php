<?php
namespace App\Http\Controllers\Admin;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Services\SellerApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class SellerReviewController extends Controller
{
 public function index(): View {$sellers=SellerProfile::with('user')->where('status',SellerStatus::Pending)->oldest()->paginate(20);return view('admin.sellers.index',compact('sellers'));}
 public function approve(SellerProfile $sellerProfile,SellerApprovalService $service): RedirectResponse {$service->approve($sellerProfile,auth()->user());return back()->with('status','Seller approved.');}
 public function reject(SellerProfile $sellerProfile,SellerApprovalService $service): RedirectResponse {$data=request()->validate(['reason'=>['required','string','max:2000']]);$service->reject($sellerProfile,auth()->user(),$data['reason']);return back()->with('status','Seller application rejected.');}
}
