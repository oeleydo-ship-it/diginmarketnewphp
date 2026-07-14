<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::create('blog_posts',function(Blueprint $t){$t->id();$t->string('title');$t->string('slug')->unique();$t->string('excerpt',500)->nullable();$t->longText('body');$t->string('meta_title')->nullable();$t->string('meta_description',500)->nullable();$t->string('status',24)->default('draft')->index();$t->timestamp('published_at')->nullable()->index();$t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();});
  Schema::create('menu_items',function(Blueprint $t){$t->id();$t->string('location',40)->index();$t->string('label',100);$t->string('url');$t->unsignedInteger('display_order')->default(0);$t->boolean('is_active')->default(true);$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('menu_items');Schema::dropIfExists('blog_posts');}
};
