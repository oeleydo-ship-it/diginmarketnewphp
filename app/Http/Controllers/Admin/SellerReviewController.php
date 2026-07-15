<?php
namespace App\Http\Controllers\Admin;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\AuditLog;
use App\Services\SellerApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
class SellerReviewController extends Controller
{
 public function index(): View {$sellers=SellerProfile::with('user')->where('status',SellerStatus::Pending)->oldest()->paginate(20);$approved=SellerProfile::with('user')->where('status',SellerStatus::Approved)->withCount('user')->orderByDesc('is_featured')->orderBy('display_name')->get();return view('admin.sellers.index',compact('sellers','approved'));}
 public function feature(SellerProfile $sellerProfile): RedirectResponse {abort_unless($sellerProfile->status===SellerStatus::Approved,422,'Only approved sellers can be featured.');$sellerProfile->update(['is_featured'=>!$sellerProfile->is_featured]);Cache::forget(\App\Http\Controllers\HomeController::CACHE_KEY);AuditLog::create(['user_id'=>auth()->id(),'action'=>'seller.featured','entity_type'=>SellerProfile::class,'entity_id'=>$sellerProfile->id,'new_values'=>['is_featured'=>$sellerProfile->is_featured],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);return back()->with('status',$sellerProfile->display_name.($sellerProfile->is_featured?' is now a featured author.':' is no longer featured.'));}
 public function approve(SellerProfile $sellerProfile,SellerApprovalService $service): RedirectResponse {$service->approve($sellerProfile,auth()->user());return back()->with('status','Seller approved.');}
 public function reject(SellerProfile $sellerProfile,SellerApprovalService $service): RedirectResponse {$data=request()->validate(['reason'=>['required','string','max:2000']]);$service->reject($sellerProfile,auth()->user(),$data['reason']);return back()->with('status','Seller application rejected.');}
}
