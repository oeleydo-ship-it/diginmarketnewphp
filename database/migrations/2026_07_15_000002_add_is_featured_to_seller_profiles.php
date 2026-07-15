<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('seller_profiles',fn(Blueprint $t)=>$t->boolean('is_featured')->default(false)->index()->after('status'));
 }
 public function down(): void
 {
  Schema::table('seller_profiles',fn(Blueprint $t)=>$t->dropColumn('is_featured'));
 }
};
