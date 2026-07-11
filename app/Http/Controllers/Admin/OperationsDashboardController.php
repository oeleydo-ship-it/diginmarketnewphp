<?php
namespace App\Http\Controllers\Admin;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\SellerProfile;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Contracts\View\View;
class OperationsDashboardController extends Controller
{
 public function __invoke(): View
 {
  $metrics=[
   'revenue'=>(float)Order::where('payment_status','paid')->sum('total'),
   'paid_orders'=>Order::where('payment_status','paid')->count(),
   'customers'=>User::count(),
   'published_products'=>Product::where('status','published')->count(),
   'pending_sellers'=>SellerProfile::where('status',SellerStatus::Pending)->count(),
   'pending_products'=>Product::whereIn('status',['submitted','under_review'])->count(),
   'open_refunds'=>RefundRequest::whereIn('status',['submitted','under_review'])->count(),
   'pending_withdrawals'=>WithdrawalRequest::whereIn('status',['pending','under_review'])->count(),
   'open_tickets'=>SupportTicket::whereIn('status',['open','awaiting_reply'])->count(),
  ];
  $recentOrders=Order::with('user')->latest()->limit(8)->get();
  $recentAudits=AuditLog::with('user')->latest('created_at')->limit(10)->get();
  return view('admin.dashboard',compact('metrics','recentOrders','recentAudits'));
 }
}
