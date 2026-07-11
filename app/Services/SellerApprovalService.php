<?php
namespace App\Services;
use App\Enums\SellerStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class SellerApprovalService
{
 public function approve(SellerProfile $profile,User $admin): void { DB::transaction(function()use($profile,$admin){$old=$profile->status->value;$profile->update(['status'=>SellerStatus::Approved,'reviewed_at'=>now(),'rejection_reason'=>null]);$profile->user->roles()->syncWithoutDetaching([Role::where('slug','seller')->firstOrFail()->id]);AuditLog::create(['user_id'=>$admin->id,'action'=>'seller.approved','entity_type'=>SellerProfile::class,'entity_id'=>$profile->id,'old_values'=>['status'=>$old],'new_values'=>['status'=>'approved'],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);}); }
}