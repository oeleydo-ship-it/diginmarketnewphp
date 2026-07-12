<?php
namespace App\Policies;
use App\Enums\SellerStatus;
use App\Models\Product;
use App\Models\User;
class ProductPolicy
{
 public function create(User $user): bool { return $user->sellerProfile?->status===SellerStatus::Approved; }
 public function update(User $user,Product $product): bool { return $product->seller_id===$user->id && in_array($product->status->value,['draft','changes_requested','rejected'],true); }
 public function submit(User $user,Product $product): bool { return $this->update($user,$product) && $product->versions()->whereHas('files')->exists(); }
 public function editAny(User $user,Product $product): bool { return $product->seller_id===$user->id && $user->sellerProfile?->status===SellerStatus::Approved; }
 public function addVersion(User $user,Product $product): bool { return $product->seller_id===$user->id && $user->sellerProfile?->status===SellerStatus::Approved && $product->status->value==='published'; }
 public function review(User $user,Product $product): bool { return $user->hasRole('administrator') && $product->seller_id!==$user->id; }
}