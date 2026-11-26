<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->string('employee_code')->primary();
            $table->foreignUuid('user_id')->unique()->constrained();
            $table->string('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreignId('position_id')->constrained();
        });
        Schema::create('signature_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained();
            $table->string('object_key')->unique();
        });
        Schema::create('sop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('sop_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version');
            $table->json('layout');
            $table->boolean('is_active')->default(true);
            $table->unique(['name', 'version']);
        });
        Schema::create('signature_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('sop_templates');
            $table->string('role');
            $table->json('allowed_modes');
        });
        Schema::create('sops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sop_code')->unique();
            $table->string('sop_title');
            $table->text('description')->nullable();
            $table->string('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('department_id')->nullable();
            $table->foreignId('category_id')->constrained('sop_categories');
            $table->string('visibility');
            $table->string('status');
            $table->uuid('current_revision_id')->nullable();
            $table->foreign(['id', 'current_revision_id'])->references(['sop_id', 'id'])->on('sop_revisions');
            $table->date('effective_date')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        Schema::create('sop_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sop_id')->constrained('sops');
            $table->foreignId('template_id')->constrained('sop_templates');
            $table->unsignedInteger('revision_number');
            $table->string('version');
            $table->json('content');
            $table->text('rendered_html')->nullable();
            $table->string('document_file')->nullable();
            $table->char('sha256_hash', 64)->nullable();
            $table->uuid('qr_verification_uuid')->nullable()->unique();
            $table->text('change_notes')->nullable();
            $table->string('revision_status');
            $table->foreignUuid('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unique(['sop_id', 'revision_number']);
            $table->unique(['sop_id', 'id']);
        });
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_id')->constrained('sop_revisions');
            $table->unsignedInteger('level');
            $table->string('role');
            $table->foreignUuid('approver_user_id')->constrained('users');
            $table->json('approver_snapshot')->nullable();
            $table->string('decision')->nullable();
            $table->text('note')->nullable();
            $table->uuid('approval_uuid')->nullable()->unique();
            $table->timestamp('decided_at')->nullable();
            $table->unique(['revision_id', 'level']);
        });
        Schema::create('signature_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_id')->constrained('sop_revisions');
            $table->foreignId('slot_id')->constrained('signature_slots');
            $table->foreignUuid('signer_user_id')->constrained('users');
            $table->json('signer_snapshot');
            $table->string('sign_mode');
            $table->string('signature_asset_key')->nullable();
            $table->uuid('sign_uuid')->unique();
            $table->timestamp('signed_at');
            $table->unique(['revision_id', 'slot_id']);
        });
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_id')->constrained('sop_revisions');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignUuid('uploaded_by')->constrained('users');
            $table->timestamp('uploaded_at');
        });
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('action');
            $table->foreignUuid('actor_id')->nullable()->constrained('users');
            $table->json('metadata');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            foreach (['audit_trails', 'attachments', 'signature_logs', 'approval_steps', 'sop_revisions', 'sops',
                'signature_slots', 'sop_templates', 'sop_categories', 'signature_assets', 'employees',
                'positions', 'organizations'] as $table) {
                Schema::dropIfExists($table);
            }
        });
    }
};
