<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('license_activations',function(Blueprint $t){$t->id();$t->foreignId('license_id')->constrained()->cascadeOnDelete();$t->string('instance_id',191);$t->string('label')->nullable();$t->string('ip_address',45)->nullable();$t->string('user_agent',512)->nullable();$t->string('status',16)->default('active')->index();$t->timestamp('activated_at');$t->timestamp('deactivated_at')->nullable();$t->timestamps();$t->unique(['license_id','instance_id']);});
 }
 public function down():void{Schema::dropIfExists('license_activations');}
};
