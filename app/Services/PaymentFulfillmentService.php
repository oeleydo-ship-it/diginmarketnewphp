<?php
namespace App\Services;
use App\Models\License;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
class PaymentFulfillmentService
{
 public function __construct(private SellerWalletService $wallets){}
 public function fulfill(int $orderId,string $paymentId,array $payload=[]): Order
 {
  return DB::transaction(function()use($orderId,$paymentId,$payload){$order=Order::query()->lockForUpdate()->with('items')->findOrFail($orderId);if($order->payment_status==='paid')return $order;$order->payments()->create(['provider'=>'stripe','provider_payment_id'=>$paymentId,'amount'=>$order->total,'currency'=>$order->currency,'status'=>'succeeded','payload'=>$payload,'paid_at'=>now()]);$order->update(['payment_status'=>'paid','status'=>'completed','paid_at'=>now()]);foreach($order->items as $item){License::firstOrCreate(['order_item_id'=>$item->id],['license_key'=>'DM-'.str()->upper(str()->random(8).'-'.str()->random(8).'-'.str()->random(8)),'product_id'=>$item->product_id,'product_version_id'=>$item->product_version_id,'user_id'=>$order->user_id,'license_type_id'=>$item->license_type_id,'status'=>'active','activation_limit'=>1,'support_expires_at'=>now()->addMonths(6)]);$this->wallets->creditSale($item,$order->currency);$item->product()->increment('sales_count');}return $order->fresh(['items.license']);});
 }
}
