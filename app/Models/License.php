<?php
namespace App\Models;
use App\Enums\ProductVersionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class License extends Model {
 protected $fillable=['license_key','product_id','product_version_id','user_id','order_item_id','license_type_id','status','activation_limit','activation_count','support_expires_at'];
 protected function casts():array{return ['support_expires_at'=>'datetime'];}
 public function orderItem():BelongsTo{return $this->belongsTo(OrderItem::class);}
 public function product():BelongsTo{return $this->belongsTo(Product::class);}
 public function version():BelongsTo{return $this->belongsTo(ProductVersion::class,'product_version_id');}
 public function activations():\Illuminate\Database\Eloquent\Relations\HasMany{return $this->hasMany(LicenseActivation::class);}
 public function downloads():\Illuminate\Database\Eloquent\Relations\HasMany{return $this->hasMany(Download::class);}
 /**
  * Every version this licence may download, newest first. Updates are free for the lifetime of the
  * product (support only covers seller assistance), so that is every published version — plus the
  * version snapshotted at purchase, which the buyer keeps even after the seller supersedes it.
  *
  * @return Collection<int,ProductVersion>
  */
 public function downloadableVersions():Collection
 {
  return ProductVersion::query()->where('product_id',$this->product_id)->where(fn($q)=>$q->where('status',ProductVersionStatus::Published)->orWhere('id',$this->product_version_id))->orderByDesc('published_at')->orderByDesc('id')->get();
 }
 public function canDownloadVersion(ProductVersion $version):bool
 {
  return (int)$version->product_id===(int)$this->product_id&&($version->status===ProductVersionStatus::Published||(int)$version->id===(int)$this->product_version_id);
 }
 /** Newest published version of the product, or the purchased one when nothing is published. */
 public function latestVersion():?ProductVersion
 {
  return $this->downloadableVersions()->first();
 }
 /** True when a published version newer than the one bought is available to download. */
 public function hasUpdate():bool
 {
  $latest=$this->latestVersion();
  return $latest!==null&&(int)$latest->id!==(int)$this->product_version_id;
 }
 /** Downloads are blocked outright once a licence is refunded, revoked or suspended. */
 public function isDownloadable():bool
 {
  return $this->status==='active';
 }
}
