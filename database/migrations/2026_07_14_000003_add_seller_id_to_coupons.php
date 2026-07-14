<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('coupons',fn(Blueprint $t)=>$t->foreignId('seller_id')->nullable()->after('code')->constrained('users')->cascadeOnDelete());
 }
 public function down(): void
 {
  Schema::table('coupons',fn(Blueprint $t)=>$t->dropConstrainedForeignId('seller_id'));
 }
};
