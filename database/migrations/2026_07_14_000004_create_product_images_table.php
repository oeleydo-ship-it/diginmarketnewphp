<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::create('product_images',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->string('disk',20)->default('public');$t->string('path');$t->string('original_name')->nullable();$t->string('alt')->nullable();$t->unsignedInteger('sort_order')->default(0);$t->timestamps();$t->index(['product_id','sort_order']);});
  Schema::table('products',fn(Blueprint $t)=>$t->string('cover_image_path')->nullable()->after('description'));
 }
 public function down(): void
 {
  Schema::table('products',fn(Blueprint $t)=>$t->dropColumn('cover_image_path'));
  Schema::dropIfExists('product_images');
 }
};
