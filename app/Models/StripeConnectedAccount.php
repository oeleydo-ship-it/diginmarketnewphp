<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StripeConnectedAccount extends Model {protected $fillable=['seller_id','stripe_account_id','account_type','status','details_submitted','charges_enabled','payouts_enabled','requirements','last_synced_at'];protected function casts():array{return ['details_submitted'=>'boolean','charges_enabled'=>'boolean','payouts_enabled'=>'boolean','requirements'=>'array','last_synced_at'=>'datetime'];}}