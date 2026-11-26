<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draft_backups', function (Blueprint $table) {
            $table->char('session_key', 64);
            $table->string('draft_key', 80);
            $table->unsignedInteger('version');
            $table->string('title', 180);
            $table->json('content');
            $table->timestamp('updated_at');
            $table->primary(['session_key', 'draft_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_backups');
    }
};
