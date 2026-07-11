<?php
namespace App\Services;
use App\Models\StripeWebhookEvent;
use Stripe\Webhook;
class StripeWebhookService
{
 public function __construct(private PaymentFulfillmentService $fulfillment){}
 public function handle(string $payload,string $signature): void
 {
  $event=Webhook::constructEvent($payload,$signature,(string)config('services.stripe.webhook_secret'));$record=StripeWebhookEvent::firstOrCreate(['stripe_event_id'=>$event->id],['event_type'=>$event->type,'payload'=>json_decode($payload,true),'processing_status'=>'pending']);if(!$record->wasRecentlyCreated||$record->processing_status==='processed')return;
  try{if($event->type==='checkout.session.completed'){$session=$event->data->object;$this->fulfillment->fulfill((int)$session->metadata->order_id,(string)($session->payment_intent?:$session->id),json_decode($payload,true));}$record->update(['processing_status'=>'processed','processed_at'=>now()]);}catch(\Throwable $e){$record->update(['processing_status'=>'failed','error_message'=>$e->getMessage(),'retry_count'=>$record->retry_count+1]);throw $e;}
 }
}