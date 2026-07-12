<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('disputes',function(Blueprint $t){$t->id();$t->string('number')->unique();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('order_id')->constrained()->restrictOnDelete();$t->foreignId('order_item_id')->unique()->constrained()->restrictOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->foreignId('seller_id')->constrained('users')->restrictOnDelete();$t->string('type',32)->default('quality');$t->text('description');$t->decimal('disputed_amount',12,2);$t->decimal('resolved_amount',12,2)->nullable();$t->string('status',32)->default('open')->index();$t->text('seller_response')->nullable();$t->text('administrator_decision')->nullable();$t->timestamp('resolved_at')->nullable();$t->timestamps();});
 }
 public function down():void{Schema::dropIfExists('disputes');}
};
