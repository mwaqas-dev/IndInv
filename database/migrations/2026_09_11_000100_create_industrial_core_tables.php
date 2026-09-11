<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('module')->index();
            $table->string('label');
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit', 20)->default('EA');
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('maximum_stock', 15, 3)->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'location_id']);
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('type')->index();
            $table->decimal('quantity', 15, 3);
            $table->decimal('balance_after', 15, 3)->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->string('reference')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project')->index();
            $table->string('version');
            $table->string('release_type');
            $table->text('description');
            $table->string('status')->default('DRAFT')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('release_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('release_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->text('comments')->nullable();
            $table->string('previous_status');
            $table->string('new_status');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
        Schema::create('release_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->text('comments')->nullable();
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->json('edit_history')->nullable();
            $table->timestamps();
        });
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk')->default('local');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action')->index();
            $table->string('module')->index();
            $table->nullableMorphs('subject');
            $table->string('subject_label')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('result')->default('SUCCESS')->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['system_settings', 'activity_logs', 'attachments', 'comments', 'release_history', 'release_approvals', 'release_components', 'releases', 'inventory_reservations', 'inventory_movements', 'inventories', 'products', 'locations', 'categories', 'permission_role', 'role_user', 'permissions', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
