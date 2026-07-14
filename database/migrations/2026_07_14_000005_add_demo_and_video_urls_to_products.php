<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('products',function(Blueprint $t){$t->string('demo_url',500)->nullable()->after('cover_image_path');$t->string('video_url',500)->nullable()->after('demo_url');});
 }
 public function down(): void
 {
  Schema::table('products',fn(Blueprint $t)=>$t->dropColumn(['demo_url','video_url']));
 }
};
