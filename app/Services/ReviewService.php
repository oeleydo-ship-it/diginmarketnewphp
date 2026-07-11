<?php
namespace App\Services;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReviewService
{
 public function create(User $user,OrderItem $item,array $data):Review
 {if($item->order->user_id!==$user->id||$item->order->payment_status!=='paid'||$item->license?->status!=='active')throw ValidationException::withMessages(['review'=>'Only eligible verified buyers may review this product.']);return DB::transaction(function()use($user,$item,$data){$review=Review::create($data+['product_id'=>$item->product_id,'user_id'=>$user->id,'order_item_id'=>$item->id,'status'=>'approved']);$this->recalculate($item->product_id);return $review;});}
 public function recalculate(int $productId):void{$query=Review::where('product_id',$productId)->where('status','approved');\App\Models\Product::whereKey($productId)->update(['average_rating'=>round((float)$query->avg('rating'),2)]);}
}