<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('users', function (Blueprint $t) {
   // Secret and recovery codes are stored encrypted (cast at the model). confirmed_at is null
   // until the user proves possession with a valid code, so a half-finished setup never locks anyone out.
   $t->text('two_factor_secret')->nullable()->after('password');
   $t->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
   $t->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
  });
 }
 public function down(): void
 {
  Schema::table('users', function (Blueprint $t) {
   $t->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
  });
 }
};
