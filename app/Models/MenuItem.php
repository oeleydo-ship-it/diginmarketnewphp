<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
class MenuItem extends Model
{
 protected $fillable=['location','label','url','display_order','is_active'];
 protected function casts(): array {return ['is_active'=>'boolean'];}
 public function scopeLocation(Builder $query,string $location): Builder {return $query->where('location',$location)->where('is_active',true)->orderBy('display_order');}
 public static function forLocation(string $location){return Cache::remember('menu:'.$location,3600,fn()=>static::query()->location($location)->get());}
 public const LOCATIONS=['header'=>'Header navigation','footer-legal'=>'Footer — Legal','footer-resources'=>'Footer — Resources'];
 public static function bustCache(): void {foreach(array_keys(self::LOCATIONS) as $location)Cache::forget('menu:'.$location);}
}
