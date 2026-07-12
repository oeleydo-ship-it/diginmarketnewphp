<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SupportMessage extends Model {protected $fillable=['support_ticket_id','user_id','message','is_internal'];protected function casts():array{return ['is_internal'=>'boolean'];}public function user():BelongsTo{return $this->belongsTo(User::class);}}
