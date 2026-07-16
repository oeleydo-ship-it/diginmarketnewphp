<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  // A fulfilled order item is no longer always "a product license": support extensions
  // reference the license they lengthen instead of minting a new one.
  Schema::table('order_items', function (Blueprint $t) {
   $t->string('item_type', 24)->default('product')->after('license_type_id')->index();
   $t->foreignId('license_id')->nullable()->after('item_type')->constrained()->nullOnDelete();
  });
  // Sellers opt products into paid support extensions by giving them a price.
  Schema::table('products', function (Blueprint $t) {
   $t->decimal('support_extension_price', 10, 2)->nullable()->after('extended_price');
   $t->unsignedTinyInteger('support_extension_months')->default(6)->after('support_extension_price');
  });
  Schema::create('bundles', function (Blueprint $t) {
   $t->id();
   $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
   $t->string('title');
   $t->string('slug')->unique();
   $t->text('description')->nullable();
   $t->decimal('price', 12, 2);
   $t->boolean('is_active')->default(true)->index();
   $t->timestamps();
  });
  Schema::create('bundle_product', function (Blueprint $t) {
   $t->id();
   $t->foreignId('bundle_id')->constrained()->cascadeOnDelete();
   $t->foreignId('product_id')->constrained()->cascadeOnDelete();
   $t->unique(['bundle_id', 'product_id']);
  });
 }
 public function down(): void
 {
  Schema::dropIfExists('bundle_product');
  Schema::dropIfExists('bundles');
  Schema::table('products', function (Blueprint $t) {
   $t->dropColumn(['support_extension_price', 'support_extension_months']);
  });
  Schema::table('order_items', function (Blueprint $t) {
   $t->dropConstrainedForeignId('license_id');
   $t->dropColumn('item_type');
  });
 }
};
