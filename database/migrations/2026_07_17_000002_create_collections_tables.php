<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::create('collections', function (Blueprint $t) {
   $t->id();
   $t->foreignId('user_id')->constrained()->cascadeOnDelete();
   $t->string('title');
   $t->string('slug')->unique();
   $t->text('description')->nullable();
   $t->boolean('is_public')->default(true)->index();
   $t->timestamps();
  });
  Schema::create('collection_product', function (Blueprint $t) {
   $t->id();
   $t->foreignId('collection_id')->constrained()->cascadeOnDelete();
   $t->foreignId('product_id')->constrained()->cascadeOnDelete();
   $t->timestamps();
   $t->unique(['collection_id', 'product_id']);
  });
 }
 public function down(): void
 {
  Schema::dropIfExists('collection_product');
  Schema::dropIfExists('collections');
 }
};
