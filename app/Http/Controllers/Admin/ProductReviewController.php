<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class ProductReviewController extends Controller
{
 public function index(): View { $products=Product::with(['seller','category'])->whereIn('status',['submitted','under_review'])->latest('submitted_at')->paginate(20);$versions=\App\Models\ProductVersion::with('product.seller')->where('status','pending_review')->whereHas('product',fn($q)=>$q->where('status','published'))->oldest()->get();return view('admin.products.index',compact('products','versions')); }
 public function approveVersion(\App\Models\ProductVersion $version,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$version->product);$service->approveVersion($version,auth()->user(),(string)request('notes'));return back()->with('status','Version published to buyers.'); }
 public function rejectVersion(\App\Models\ProductVersion $version,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$version->product);$data=request()->validate(['notes'=>['required','string','max:2000']]);$service->rejectVersion($version,auth()->user(),$data['notes']);return back()->with('status','Version rejected.'); }
 public function approve(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$product);$service->approve($product,auth()->user(),(string)request('notes'));return back()->with('status','Product published.'); }
 public function requestChanges(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$product);$data=request()->validate(['notes'=>['required','string','max:2000']]);$service->requestChanges($product,auth()->user(),$data['notes']);return back()->with('status','Changes requested from the seller.'); }
}