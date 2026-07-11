<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Comment extends Model {protected $fillable=['product_id','user_id','parent_id','content','status','is_pinned','is_seller_answer'];protected function casts():array{return ['is_pinned'=>'boolean','is_seller_answer'=>'boolean'];}public function user():BelongsTo{return $this->belongsTo(User::class);}public function replies():HasMany{return $this->hasMany(self::class,'parent_id');}}