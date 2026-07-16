<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('seller_profiles',function(Blueprint $t){$t->string('default_payout_method',20)->nullable()->after('is_featured');$t->json('default_payout_details')->nullable()->after('default_payout_method');});
 }
 public function down(): void
 {
  Schema::table('seller_profiles',fn(Blueprint $t)=>$t->dropColumn(['default_payout_method','default_payout_details']));
 }
};
