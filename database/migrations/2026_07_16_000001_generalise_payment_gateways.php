<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
/**
 * Checkout was Stripe-only, so the provider was implied by the schema rather than recorded.
 * Generalise the columns and keep every historical row by renaming in place instead of recreating.
 */
return new class extends Migration
{
 public function up(): void
 {
  Schema::table('orders',function(Blueprint $t){$t->string('payment_provider',32)->nullable()->after('status')->index();});
  Schema::table('orders',function(Blueprint $t){$t->renameColumn('stripe_checkout_session_id','provider_checkout_id');});
  // Every order that reached a gateway before this migration went through Stripe.
  DB::table('orders')->whereNotNull('provider_checkout_id')->update(['payment_provider'=>'stripe']);
  Schema::rename('stripe_webhook_events','payment_webhook_events');
  Schema::table('payment_webhook_events',function(Blueprint $t){$t->string('provider',32)->default('stripe')->after('id')->index();});
  Schema::table('payment_webhook_events',function(Blueprint $t){$t->renameColumn('stripe_event_id','event_id');});
  Schema::table('payment_webhook_events',function(Blueprint $t){
   // Event ids are only unique within a provider, so the idempotency key has to include it.
   $t->dropUnique('stripe_webhook_events_stripe_event_id_unique');
   $t->unique(['provider','event_id']);
  });
 }
 public function down(): void
 {
  Schema::table('payment_webhook_events',function(Blueprint $t){$t->dropUnique(['provider','event_id']);});
  Schema::table('payment_webhook_events',function(Blueprint $t){$t->renameColumn('event_id','stripe_event_id');});
  Schema::table('payment_webhook_events',function(Blueprint $t){$t->dropColumn('provider');});
  Schema::table('payment_webhook_events',function(Blueprint $t){$t->unique('stripe_event_id','stripe_webhook_events_stripe_event_id_unique');});
  Schema::rename('payment_webhook_events','stripe_webhook_events');
  Schema::table('orders',function(Blueprint $t){$t->renameColumn('provider_checkout_id','stripe_checkout_session_id');});
  Schema::table('orders',function(Blueprint $t){$t->dropColumn('payment_provider');});
 }
};
