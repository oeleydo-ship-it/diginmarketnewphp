<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('products',function(Blueprint $t){$t->id();$t->foreignId('seller_id')->constrained('users')->restrictOnDelete();$t->foreignId('category_id')->constrained()->restrictOnDelete();$t->string('title');$t->string('slug')->unique();$t->text('short_description');$t->longText('description');$t->decimal('regular_price',12,2);$t->decimal('extended_price',12,2)->nullable();$t->string('status',32)->default('draft')->index();$t->timestamp('submitted_at')->nullable();$t->timestamp('published_at')->nullable()->index();$t->timestamps();$t->softDeletes();$t->index(['seller_id','status']);});
  Schema::create('product_versions',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->string('version_number',50);$t->string('release_title');$t->longText('release_notes')->nullable();$t->string('status',32)->default('draft')->index();$t->timestamp('published_at')->nullable();$t->timestamps();$t->unique(['product_id','version_number']);});
  Schema::create('product_files',function(Blueprint $t){$t->id();$t->foreignId('product_version_id')->constrained()->cascadeOnDelete();$t->string('disk',50)->default('local');$t->string('path');$t->string('original_name');$t->string('mime_type',120);$t->string('extension',20);$t->unsignedBigInteger('size');$t->string('checksum',64)->index();$t->string('scan_status',24)->default('pending')->index();$t->timestamps();});
  Schema::create('product_review_submissions',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();$t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();$t->string('status',32)->default('submitted')->index();$t->text('seller_notes')->nullable();$t->text('review_notes')->nullable();$t->timestamp('submitted_at');$t->timestamp('reviewed_at')->nullable();$t->timestamps();});
 }
 public function down(): void { Schema::dropIfExists('product_review_submissions');Schema::dropIfExists('product_files');Schema::dropIfExists('product_versions');Schema::dropIfExists('products'); }
};