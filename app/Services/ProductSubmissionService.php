<?php
namespace App\Services;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class ProductSubmissionService
{
 public function submit(Product $product,User $seller): void { DB::transaction(function()use($product,$seller){$product->update(['status'=>ProductStatus::Submitted,'submitted_at'=>now()]);$product->versions()->update(['status'=>ProductVersionStatus::PendingReview]);$product->reviewSubmissions()->create(['submitted_by'=>$seller->id,'status'=>'submitted','submitted_at'=>now()]);}); }
 public function approve(Product $product,User $admin,string $notes=''): void { DB::transaction(function()use($product,$admin,$notes){$old=$product->status->value;$product->update(['status'=>ProductStatus::Published,'published_at'=>now()]);$product->versions()->update(['status'=>ProductVersionStatus::Published,'published_at'=>now()]);$product->reviewSubmissions()->latest()->firstOrFail()->update(['reviewed_by'=>$admin->id,'status'=>'approved','review_notes'=>$notes,'reviewed_at'=>now()]);AuditLog::create(['user_id'=>$admin->id,'action'=>'product.approved','entity_type'=>Product::class,'entity_id'=>$product->id,'old_values'=>['status'=>$old],'new_values'=>['status'=>'published'],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);}); }
 public function requestChanges(Product $product,User $admin,string $notes): void { DB::transaction(function()use($product,$admin,$notes){$old=$product->status->value;$product->update(['status'=>ProductStatus::ChangesRequested]);$product->versions()->where('status',ProductVersionStatus::PendingReview)->update(['status'=>ProductVersionStatus::Draft]);$product->reviewSubmissions()->latest()->firstOrFail()->update(['reviewed_by'=>$admin->id,'status'=>'changes_requested','review_notes'=>$notes,'reviewed_at'=>now()]);AuditLog::create(['user_id'=>$admin->id,'action'=>'product.changes_requested','entity_type'=>Product::class,'entity_id'=>$product->id,'old_values'=>['status'=>$old],'new_values'=>['status'=>'changes_requested','notes'=>$notes],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);}); }
}