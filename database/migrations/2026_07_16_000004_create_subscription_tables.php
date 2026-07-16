<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
 public function up(): void
 {
  Schema::create('subscription_plans', function (Blueprint $t) {
   $t->id();
   $t->string('name');
   $t->string('slug')->unique();
   $t->decimal('price', 10, 2)->default(0);
   $t->string('billing_period', 16); // weekly | monthly | yearly | lifetime
   // Null commission_rate = fall through to normal commission rules; a value overrides them
   // for this seller. Null listing_limit = unlimited listings.
   $t->decimal('commission_rate', 5, 2)->nullable();
   $t->unsignedInteger('listing_limit')->nullable();
   $t->json('features')->nullable();
   $t->boolean('is_active')->default(true)->index();
   $t->unsignedInteger('sort_order')->default(0);
   $t->timestamps();
  });
  Schema::create('seller_subscriptions', function (Blueprint $t) {
   $t->id();
   $t->foreignId('user_id')->constrained()->cascadeOnDelete();
   $t->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
   $t->string('status', 16)->default('pending'); // pending | active | expired | cancelled
   // Plan terms are snapshotted so later plan edits never rewrite a seller's agreed deal.
   $t->decimal('price', 10, 2);
   $t->string('billing_period', 16);
   $t->decimal('commission_rate', 5, 2)->nullable();
   $t->unsignedInteger('listing_limit')->nullable();
   $t->timestamp('starts_at')->nullable();
   $t->timestamp('ends_at')->nullable(); // null once active means lifetime
   $t->string('payment_reference')->nullable();
   $t->timestamp('cancelled_at')->nullable();
   $t->timestamps();
   $t->index(['user_id', 'status']);
  });
 }
 public function down(): void
 {
  Schema::dropIfExists('seller_subscriptions');
  Schema::dropIfExists('subscription_plans');
 }
};
