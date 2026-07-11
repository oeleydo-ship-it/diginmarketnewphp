<?php
namespace App\Http\Controllers;
use App\Models\Download;
use App\Models\License;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
class DownloadController extends Controller
{
 public function __invoke(Request $request,License $license): StreamedResponse
 {
  abort_unless($request->hasValidSignature(),403);abort_unless($license->user_id===auth()->id()&&$license->status==='active',403);$license->load('orderItem.order','version.files');abort_unless($license->orderItem->order->payment_status==='paid',403);$file=$license->version?->files()->where('scan_status','!=','infected')->firstOrFail();Download::create(['user_id'=>auth()->id(),'product_id'=>$license->product_id,'product_version_id'=>$license->product_version_id,'order_id'=>$license->orderItem->order_id,'license_id'=>$license->id,'ip_address'=>$request->ip(),'user_agent'=>$request->userAgent(),'downloaded_at'=>now()]);return Storage::disk($file->disk)->download($file->path,$file->original_name);
 }
}