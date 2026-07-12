<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('affiliate_profiles',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();$t->string('code',24)->unique();$t->decimal('commission_rate',5,2);$t->unsignedBigInteger('clicks')->default(0);$t->unsignedInteger('referred_orders')->default(0);$t->decimal('total_earnings',12,2)->default(0);$t->string('status',16)->default('active')->index();$t->timestamps();});
  Schema::create('affiliate_earnings',function(Blueprint $t){$t->id();$t->foreignId('affiliate_profile_id')->constrained()->restrictOnDelete();$t->foreignId('order_id')->unique()->constrained()->restrictOnDelete();$t->decimal('amount',12,2);$t->string('currency',3);$t->string('status',16)->default('pending')->index();$t->timestamps();});
  Schema::table('orders',function(Blueprint $t){$t->foreignId('affiliate_profile_id')->nullable()->after('user_id')->constrained()->nullOnDelete();});
 }
 public function down():void{Schema::table('orders',function(Blueprint $t){$t->dropConstrainedForeignId('affiliate_profile_id');});Schema::dropIfExists('affiliate_earnings');Schema::dropIfExists('affiliate_profiles');}
};
