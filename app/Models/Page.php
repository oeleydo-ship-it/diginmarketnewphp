<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class Page extends Model
{
 protected $fillable=['title','slug','excerpt','body','meta_title','meta_description','status','published_at','created_by'];
 protected function casts(): array {return ['published_at'=>'datetime'];}
 public function scopePublished(Builder $query): Builder {return $query->where('status','published')->whereNotNull('published_at')->where('published_at','<=',now());}
}
