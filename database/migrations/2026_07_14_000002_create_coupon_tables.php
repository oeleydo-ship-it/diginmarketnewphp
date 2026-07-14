<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::create('coupons',function(Blueprint $t){$t->id();$t->string('code',40)->unique();$t->string('description')->nullable();$t->string('type',12);$t->decimal('value',12,2);$t->decimal('min_cart_total',12,2)->nullable();$t->unsignedInteger('max_uses')->nullable();$t->unsignedInteger('max_uses_per_user')->default(1);$t->unsignedInteger('used_count')->default(0);$t->timestamp('starts_at')->nullable();$t->timestamp('ends_at')->nullable();$t->boolean('is_active')->default(true)->index();$t->timestamps();});
  Schema::create('coupon_usages',function(Blueprint $t){$t->id();$t->foreignId('coupon_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();$t->timestamp('created_at')->useCurrent();$t->index(['coupon_id','user_id']);});
  Schema::table('carts',fn(Blueprint $t)=>$t->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete());
  Schema::table('orders',function(Blueprint $t){$t->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();$t->string('coupon_code',40)->nullable();});
 }
 public function down(): void
 {
  Schema::table('orders',function(Blueprint $t){$t->dropConstrainedForeignId('coupon_id');$t->dropColumn('coupon_code');});
  Schema::table('carts',fn(Blueprint $t)=>$t->dropConstrainedForeignId('coupon_id'));
  Schema::dropIfExists('coupon_usages');Schema::dropIfExists('coupons');
 }
};
