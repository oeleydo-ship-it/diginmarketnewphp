<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CommissionRule extends Model {protected $fillable=['scope_type','scope_id','rate','fixed_fee','priority','is_active','starts_at','ends_at'];protected function casts():array{return ['rate'=>'decimal:2','fixed_fee'=>'decimal:2','is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];}}