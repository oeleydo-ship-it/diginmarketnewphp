<?php
namespace App\Http\Controllers;
use App\Models\Download;
use App\Models\License;
use App\Models\ProductVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;
class DownloadController extends Controller
{
 /**
  * Serve one version of a licensed product. Without an explicit version the buyer gets the newest
  * one they are entitled to, so a licence bought at 1.0.0 keeps working after the seller ships 2.0.0
  * — previously the download was pinned to the purchase-time version forever and updates were
  * unreachable even though the update-check API announced them.
  */
 public function __invoke(Request $request,License $license,?ProductVersion $version=null): StreamedResponse
 {
  abort_unless($request->hasValidSignature(),403);
  abort_unless($license->user_id===auth()->id()&&$license->isDownloadable(),403);
  $license->load('orderItem.order');
  // A refund or dispute on a sibling item moves the order off 'paid'; the licence status is what
  // gates this item, so accept any settled order here.
  abort_unless($license->orderItem?->order?->isSettled(),403);
  $version=$version&&$version->exists?$version:$license->latestVersion();
  abort_unless($version&&$license->canDownloadVersion($version),404,'That version is not available for this licence.');
  $file=$version->files()->where('scan_status','!=','infected')->orderBy('id')->first();
  abort_unless($file,404,'This version has no downloadable file yet.');
  Download::create(['user_id'=>auth()->id(),'product_id'=>$license->product_id,'product_version_id'=>$version->id,'order_id'=>$license->orderItem->order_id,'license_id'=>$license->id,'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),'downloaded_at'=>now()]);

  return Storage::disk($file->disk)->download($file->path,$file->original_name);
 }

 /** Buyer-facing library: every active licence with its versions, plus the full download log. */
 public function index(): View
 {
  $licenses=License::query()->where('user_id',auth()->id())->with(['product','version','orderItem.order'])->whereHas('orderItem.order',fn($q)=>$q->settled())->latest('id')->get();
  $library=$licenses->map(function(License $license){
   $versions=$license->downloadableVersions();
   return ['license'=>$license,'versions'=>$versions->map(fn(ProductVersion $version)=>['version'=>$version,'url'=>$license->isDownloadable()?self::signedUrl($license,$version):null,'is_purchased'=>(int)$version->id===(int)$license->product_version_id])->all(),'has_update'=>$license->hasUpdate()];
  });
  $downloads=Download::query()->where('user_id',auth()->id())->with(['product','version'])->latest('downloaded_at')->paginate(20);

  return view('downloads.index',compact('library','downloads'));
 }

 /** Short-lived signed link for one licence/version pair. */
 public static function signedUrl(License $license,?ProductVersion $version=null): string
 {
  return URL::temporarySignedRoute('downloads.show',now()->addMinutes(10),array_filter(['license'=>$license->id,'version'=>$version?->id]));
 }
}
