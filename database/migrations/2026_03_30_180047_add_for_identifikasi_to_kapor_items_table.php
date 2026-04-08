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
        Schema::table('kapor_items', function (Blueprint $table) {
            $table->boolean('for_identifikasi')
                ->default(true)
                ->after('is_active')
                ->comment('Tersedia untuk dipilih pada form Identifikasi Kebutuhan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kapor_items', function (Blueprint $table) {
            $table->dropColumn('for_identifikasi');
        });
    }
};
