<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('products',fn(Blueprint $t)=>$t->boolean('business_license_enabled')->default(true)->after('extended_price'));
  DB::table('license_types')->where('slug','extended')->update(['name'=>'Business License','description'=>'Use in one end product where end users may be charged.']);
 }
 public function down(): void
 {
  DB::table('license_types')->where('slug','extended')->update(['name'=>'Extended License']);
  Schema::table('products',fn(Blueprint $t)=>$t->dropColumn('business_license_enabled'));
 }
};
