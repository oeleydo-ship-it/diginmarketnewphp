<?php
namespace App\Http\Middleware;
use App\Models\AffiliateProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
class TrackAffiliateReferral
{
 public const COOKIE='dm_aff';
 public function handle(Request $request,Closure $next)
 {
  $code=(string)$request->query('ref','');
  if($code!==''&&$request->isMethod('GET')){
   $profile=AffiliateProfile::where('code',$code)->where('status','active')->first();
   if($profile&&$request->cookie(self::COOKIE)!==$profile->code){
    $profile->increment('clicks');
    Cookie::queue(self::COOKIE,$profile->code,60*24*30);
   }
  }
  return $next($request);
 }
}
