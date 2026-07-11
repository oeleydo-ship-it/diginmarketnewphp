<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NotificationPreference extends Model {protected $fillable=['user_id','email_sales','email_product_updates','email_support','email_marketing','in_app'];protected function casts():array{return ['email_sales'=>'boolean','email_product_updates'=>'boolean','email_support'=>'boolean','email_marketing'=>'boolean','in_app'=>'boolean'];}}