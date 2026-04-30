<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom `eligible_satker_count` menyimpan jumlah satker yang berhak memilih item ini.
     * NULL  = tidak ada batasan, gunakan total satker aktif di aplikasi.
     * Angka = gunakan angka ini sebagai penyebut perhitungan persentase.
     */
    public function up(): void
    {
        Schema::table('identifikasi_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('eligible_satker_count')
                  ->nullable()
                  ->after('description')
                  ->comment('Jumlah satker yang berhak memilih item ini. NULL = semua satker.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('identifikasi_items', function (Blueprint $table) {
            $table->dropColumn('eligible_satker_count');
        });
    }
};

