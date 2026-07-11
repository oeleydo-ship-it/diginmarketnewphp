<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StripeWebhookEvent extends Model {protected $fillable=['stripe_event_id','event_type','payload','processing_status','processed_at','error_message','retry_count'];protected function casts():array{return ['payload'=>'array','processed_at'=>'datetime'];}}