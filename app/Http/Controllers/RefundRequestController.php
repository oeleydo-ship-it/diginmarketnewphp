<?php
namespace App\Http\Controllers;
use App\Models\OrderItem;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
class RefundRequestController extends Controller {public function store(OrderItem $orderItem,RefundService $service):RedirectResponse{$data=request()->validate(['reason'=>['required','string','max:120'],'description'=>['required','string','min:20','max:5000']]);$service->request(auth()->user(),$orderItem->load('order'),$data);return back()->with('status','Refund request submitted.');}}