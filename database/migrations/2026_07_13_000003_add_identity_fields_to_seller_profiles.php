<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('seller_profiles',function(Blueprint $t){$t->string('full_name')->nullable()->after('user_id');$t->string('address')->nullable()->after('country');$t->string('city',120)->nullable()->after('address');$t->string('postal_code',20)->nullable()->after('city');});
 }
 public function down():void{Schema::table('seller_profiles',function(Blueprint $t){$t->dropColumn(['full_name','address','city','postal_code']);});}
};
