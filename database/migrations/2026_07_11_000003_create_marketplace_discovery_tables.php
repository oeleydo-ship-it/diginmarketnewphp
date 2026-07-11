<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('products',function(Blueprint $t){$t->unsignedBigInteger('views_count')->default(0)->index();$t->unsignedBigInteger('sales_count')->default(0)->index();$t->decimal('average_rating',3,2)->default(0)->index();$t->boolean('is_featured')->default(false)->index();$t->boolean('is_trending')->default(false)->index();$t->string('seo_title')->nullable();$t->string('seo_description',320)->nullable();});
  Schema::create('wishlists',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();$t->timestamps();});
  Schema::create('wishlist_items',function(Blueprint $t){$t->id();$t->foreignId('wishlist_id')->constrained()->cascadeOnDelete();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->timestamps();$t->unique(['wishlist_id','product_id']);});
  Schema::create('seller_followers',function(Blueprint $t){$t->id();$t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();$t->foreignId('follower_id')->constrained('users')->cascadeOnDelete();$t->timestamps();$t->unique(['seller_id','follower_id']);});
 }
 public function down(): void {Schema::dropIfExists('seller_followers');Schema::dropIfExists('wishlist_items');Schema::dropIfExists('wishlists');Schema::table('products',fn(Blueprint $t)=>$t->dropColumn(['views_count','sales_count','average_rating','is_featured','is_trending','seo_title','seo_description']));}
};