<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LicenseType extends Model {protected $fillable=['name','slug','description','allows_paid_end_product','is_active'];protected function casts():array{return ['allows_paid_end_product'=>'boolean','is_active'=>'boolean'];}}