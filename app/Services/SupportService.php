<?php
namespace App\Services;
use App\Models\License;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SupportService {public function open(User $user,License $license,array $data):SupportTicket{if($license->user_id!==$user->id||$license->status!=='active'||$license->orderItem->order->payment_status!=='paid')throw ValidationException::withMessages(['license'=>'An active purchased license is required.']);return DB::transaction(function()use($user,$license,$data){$ticket=SupportTicket::create(['number'=>'SUP-'.str()->upper(str()->random(10)),'user_id'=>$user->id,'seller_id'=>$license->orderItem->seller_id,'product_id'=>$license->product_id,'order_id'=>$license->orderItem->order_id,'license_id'=>$license->id,'subject'=>$data['subject'],'priority'=>$data['priority']??'normal']);$ticket->messages()->create(['user_id'=>$user->id,'message'=>$data['message']]);return $ticket;});}}