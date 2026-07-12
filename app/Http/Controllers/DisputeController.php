<?php
namespace App\Http\Controllers;
use App\Models\OrderItem;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
class DisputeController extends Controller {public function store(OrderItem $orderItem,DisputeService $service):RedirectResponse{$data=request()->validate(['type'=>['required','in:quality,not_as_described,not_delivered,unauthorized'],'description'=>['required','string','min:20','max:5000']]);$service->open(auth()->user(),$orderItem->load('order','license'),$data);return back()->with('status','Dispute opened. Downloads are paused while we investigate.');}}
