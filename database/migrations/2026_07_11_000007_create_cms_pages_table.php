<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::create('pages',function(Blueprint $t){$t->id();$t->string('title');$t->string('slug')->unique();$t->string('excerpt',500)->nullable();$t->longText('body');$t->string('meta_title')->nullable();$t->string('meta_description',500)->nullable();$t->string('status',24)->default('draft')->index();$t->timestamp('published_at')->nullable();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('pages');}
};
