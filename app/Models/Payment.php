<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {protected $fillable=['order_id','provider','provider_payment_id','amount','currency','status','payload','paid_at'];protected function casts():array{return ['payload'=>'array','paid_at'=>'datetime'];}}