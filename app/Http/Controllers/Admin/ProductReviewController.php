<?php
namespace App\Http\Controllers\Admin;
use App\Enums\ProductVersionStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use App\Services\ProductSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ProductReviewController extends Controller
{
 public function index(): View
 {
  $products=Product::with(['seller','category','images','versions.files'])->whereIn('status',['submitted','under_review'])->latest('submitted_at')->paginate(20);
  $versions=ProductVersion::with(['product.seller','product.images','product.category','files'])->where('status','pending_review')->whereHas('product',fn($q)=>$q->where('status','published'))->oldest()->get();
  return view('admin.products.index',compact('products','versions'));
 }
 public function show(Product $product): View
 {
  $this->authorize('review',$product);
  $product->load(['seller','category','images','versions.files']);
  return view('admin.products.show',compact('product'));
 }
 public function approveVersion(ProductVersion $version,ProductSubmissionService $service): RedirectResponse { $version->loadMissing('product');$this->authorize('review',$version->product);$service->approveVersion($version,auth()->user(),(string)request('notes'));return back()->with('status','Version published to buyers.'); }
 public function rejectVersion(ProductVersion $version,ProductSubmissionService $service): RedirectResponse { $version->loadMissing('product');$this->authorize('review',$version->product);$data=request()->validate(['notes'=>['required','string','max:2000']]);$service->rejectVersion($version,auth()->user(),$data['notes']);return back()->with('status','Version rejected.'); }
 public function approve(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$product);$service->approve($product,auth()->user(),(string)request('notes'));return back()->with('status','Product published.'); }
 public function requestChanges(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('review',$product);$data=request()->validate(['notes'=>['required','string','max:2000']]);$service->requestChanges($product,auth()->user(),$data['notes']);return back()->with('status','Changes requested from the seller.'); }
 public function download(Product $product): StreamedResponse
 {
  $this->authorize('review',$product);
  $version=$product->reviewVersion();
  abort_unless($version,404,'This product has no submitted archive yet.');
  return $this->streamArchive($version,$product);
 }
 public function downloadVersion(ProductVersion $version): StreamedResponse
 {
  $version->loadMissing('product');
  $this->authorize('review',$version->product);
  abort_unless($version->status===ProductVersionStatus::PendingReview,404);
  return $this->streamArchive($version,$version->product);
 }
 private function streamArchive(ProductVersion $version,Product $product): StreamedResponse
 {
  $file=$version->downloadableFile();
  abort_unless($file instanceof ProductFile && $file->path,404,'This version has no downloadable file yet.');
  abort_unless(Storage::disk($file->disk)->exists($file->path),404,'The submitted archive is missing from storage.');
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'product.archive_downloaded','entity_type'=>Product::class,'entity_id'=>$product->id,'old_values'=>null,'new_values'=>['product_version_id'=>$version->id,'original_name'=>$file->original_name],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return Storage::disk($file->disk)->download($file->path,$file->original_name ?: 'product.zip');
 }
}
