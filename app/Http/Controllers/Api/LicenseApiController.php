<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\LicenseActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class LicenseApiController extends Controller
{
 public function __construct(private LicenseActivationService $service){}
 public function verify(Request $request):JsonResponse
 {
  $data=$request->validate(['license_key'=>['required','string','max:120']]);
  $license=$this->service->findUsableLicense($data['license_key']);
  return response()->json(['valid'=>true,'product'=>$license->product->title,'license_type'=>$license->license_type_id,'activations_used'=>(int)$license->activation_count,'activation_limit'=>$license->activation_limit?(int)$license->activation_limit:null,'support_expires_at'=>$license->support_expires_at?->toIso8601String()]);
 }
 public function activate(Request $request):JsonResponse
 {
  $data=$request->validate(['license_key'=>['required','string','max:120'],'instance_id'=>['required','string','max:191'],'label'=>['nullable','string','max:191']]);
  $license=$this->service->findUsableLicense($data['license_key']);
  $activation=$this->service->activate($license,$data['instance_id'],$data['label']??null,$request->ip(),(string)$request->userAgent());
  return response()->json(['activated'=>true,'instance_id'=>$activation->instance_id,'activated_at'=>$activation->activated_at->toIso8601String(),'activations_used'=>(int)$license->fresh()->activation_count,'activation_limit'=>$license->activation_limit?(int)$license->activation_limit:null],201);
 }
 public function deactivate(Request $request):JsonResponse
 {
  $data=$request->validate(['license_key'=>['required','string','max:120'],'instance_id'=>['required','string','max:191']]);
  $license=$this->service->findUsableLicense($data['license_key']);
  $this->service->deactivate($license,$data['instance_id']);
  return response()->json(['deactivated'=>true,'activations_used'=>(int)$license->fresh()->activation_count]);
 }
 public function updateCheck(Request $request):JsonResponse
 {
  $data=$request->validate(['license_key'=>['required','string','max:120']]);
  $license=$this->service->findUsableLicense($data['license_key']);
  return response()->json($this->service->updateEligibility($license->load('version','product')));
 }
}
