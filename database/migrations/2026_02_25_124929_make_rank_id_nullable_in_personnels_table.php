<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            // Agar personel dengan data belum lengkap (tanpa pangkat) tetap bisa disimpan saat import
            $table->foreignId('rank_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->foreignId('rank_id')->nullable(false)->change();
        });
    }
};
