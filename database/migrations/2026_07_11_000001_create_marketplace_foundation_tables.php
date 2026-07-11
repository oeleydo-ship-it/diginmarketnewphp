<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) { $table->string('status', 24)->default('active')->index(); $table->string('phone', 40)->nullable(); $table->string('country', 2)->nullable(); $table->timestamp('last_login_at')->nullable(); });
        Schema::create('roles', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('description')->nullable(); $table->timestamps(); });
        Schema::create('role_user', function (Blueprint $table) { $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamps(); $table->primary(['role_id', 'user_id']); });
        Schema::create('seller_profiles', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); $table->string('display_name'); $table->string('username')->unique(); $table->string('country', 2); $table->string('phone', 40)->nullable(); $table->text('biography')->nullable(); $table->string('business_name')->nullable(); $table->string('website')->nullable(); $table->string('status', 24)->default('draft')->index(); $table->text('rejection_reason')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->timestamps(); $table->softDeletes(); });
        Schema::create('categories', function (Blueprint $table) { $table->id(); $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete(); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable(); $table->string('icon')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->unsignedInteger('display_order')->default(0); $table->decimal('commission_rate', 5, 2)->nullable(); $table->timestamps(); $table->softDeletes(); $table->index(['parent_id', 'is_active', 'display_order']); });
        Schema::create('settings', function (Blueprint $table) { $table->id(); $table->string('group')->index(); $table->string('key')->unique(); $table->longText('value')->nullable(); $table->string('type', 24)->default('string'); $table->boolean('is_encrypted')->default(false); $table->boolean('is_public')->default(false); $table->timestamps(); });
        Schema::create('audit_logs', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('action')->index(); $table->string('entity_type')->nullable(); $table->unsignedBigInteger('entity_id')->nullable(); $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->string('ip_address', 45)->nullable(); $table->text('user_agent')->nullable(); $table->timestamp('created_at')->useCurrent(); $table->index(['entity_type', 'entity_id']); });
    }
    public function down(): void
    {
        Schema::dropIfExists('audit_logs'); Schema::dropIfExists('settings'); Schema::dropIfExists('categories'); Schema::dropIfExists('seller_profiles'); Schema::dropIfExists('role_user'); Schema::dropIfExists('roles');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['status', 'phone', 'country', 'last_login_at']));
    }
};
