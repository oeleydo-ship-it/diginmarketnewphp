<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Download extends Model {protected $fillable=['user_id','product_id','product_version_id','order_id','license_id','ip_address','user_agent','downloaded_at'];protected function casts():array{return ['downloaded_at'=>'datetime'];}}