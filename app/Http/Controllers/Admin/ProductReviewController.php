<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class ProductReviewController extends Controller
{
 public function index(): View { $products=Product::with(['seller','category'])->whereIn('status',['submitted','under_review'])->latest('submitted_at')->paginate(20);return view('admin.products.index',compact('products')); }
 public function approve(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$product);$service->approve($product,auth()->user(),(string)request('notes'));return back()->with('status','Product published.'); }
 public function requestChanges(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$product);$data=request()->validate(['notes'=>['required','string','max:2000']]);$service->requestChanges($product,auth()->user(),$data['notes']);return back()->with('status','Changes requested from the seller.'); }
}