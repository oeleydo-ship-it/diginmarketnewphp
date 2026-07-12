<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LicenseActivation extends Model {protected $fillable=['license_id','instance_id','label','ip_address','user_agent','status','activated_at','deactivated_at'];protected function casts():array{return ['activated_at'=>'datetime','deactivated_at'=>'datetime'];}public function license():BelongsTo{return $this->belongsTo(License::class);}}
