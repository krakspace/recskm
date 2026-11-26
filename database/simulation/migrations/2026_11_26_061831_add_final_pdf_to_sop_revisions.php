<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sop_revisions', function (Blueprint $table) {
            $table->longText('final_pdf')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sop_revisions', function (Blueprint $table) {
            $table->dropColumn('final_pdf');
        });
    }
};
