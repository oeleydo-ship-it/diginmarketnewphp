<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupportMessage extends Model {protected $fillable=['support_ticket_id','user_id','message','is_internal'];protected function casts():array{return ['is_internal'=>'boolean'];}}