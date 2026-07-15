<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('withdrawal_requests',function(Blueprint $t){$t->string('payout_method',20)->default('stripe')->after('status');$t->json('payout_details')->nullable()->after('payout_method');$t->string('payout_reference')->nullable()->after('stripe_payout_id');});
 }
 public function down(): void
 {
  Schema::table('withdrawal_requests',fn(Blueprint $t)=>$t->dropColumn(['payout_method','payout_details','payout_reference']));
 }
};
