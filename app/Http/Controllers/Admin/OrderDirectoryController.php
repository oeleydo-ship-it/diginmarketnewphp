<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\PaymentFulfillmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
class OrderDirectoryController extends Controller
{
 /**
  * Bank transfers arrive outside any gateway, so an administrator confirming receipt is the
  * only signal that funds landed. Fulfilment stays in the same idempotent path a webhook uses.
  */
 public function confirmTransfer(Order $order,PaymentFulfillmentService $fulfillment): RedirectResponse
 {
  abort_unless($order->payment_provider==='bank_transfer',404);
  abort_unless($order->payment_status==='pending',422,'This order is no longer awaiting payment.');
  $reference=request()->validate(['reference'=>['required','string','max:120']])['reference'];
  $fulfillment->fulfill($order->id,'bt:'.$reference,'bank_transfer',['confirmed_by'=>auth()->id(),'reference'=>$reference]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'order.transfer_confirmed','entity_type'=>Order::class,'entity_id'=>$order->id,'old_values'=>['payment_status'=>'pending'],'new_values'=>['payment_status'=>'paid','reference'=>$reference],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Transfer confirmed for order '.$order->number.'.');
 }

 public function index(): View
 {
  $orders=$this->filteredOrders()->with(['user','items'])->latest()->paginate(10)->withQueryString();
  $paidRevenue=(float)Order::where('payment_status','paid')->sum('total');
  $completed=Order::whereIn('payment_status',['paid','partially_refunded'])->count();
  $metrics=[
   'revenue'=>$paidRevenue,
   'pending'=>Order::where('payment_status','pending')->count(),
   'refund_rate'=>$completed ? round(Order::where('payment_status','partially_refunded')->count()/$completed*100,1) : 0,
   'disputes'=>\App\Models\Dispute::whereIn('status',['open','under_review'])->count(),
  ];
  $categories=Category::orderBy('name')->get(['id','name']);
  return view('admin.orders.index',compact('orders','metrics','categories'));
 }

 public function export(): StreamedResponse
 {
  $orders=$this->filteredOrders()->with(['user','items'])->latest()->get();
  return response()->streamDownload(function() use ($orders): void {
   $handle=fopen('php://output','w');
   fputcsv($handle,['Order ID','Customer','Email','Date','Amount','Currency','Payment status','Order status','License']);
   foreach($orders as $order) fputcsv($handle,[$order->number,$order->user?->name,$order->user?->email,$order->created_at->toDateString(),$order->total,$order->currency,$order->payment_status,$order->status,$order->items->first()?->license_name]);
   fclose($handle);
  },'orders-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv']);
 }

 private function filteredOrders(): Builder
 {
  return Order::query()
   ->when(request('q'),fn(Builder $query,string $term)=>$query->where(function(Builder $query) use ($term): void {
    $query->where('number','like','%'.$term.'%')->orWhereHas('user',fn(Builder $user)=>$user->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%'));
   }))
   ->when(request('status'),fn(Builder $query,string $status)=>$query->where('payment_status',$status))
   ->when(request('category'),fn(Builder $query,string $category)=>$query->whereHas('items.product',fn(Builder $product)=>$product->where('category_id',$category)))
   ->when(request('from'),fn(Builder $query,string $from)=>$query->whereDate('created_at','>=',$from))
   ->when(request('to'),fn(Builder $query,string $to)=>$query->whereDate('created_at','<=',$to));
 }
}
