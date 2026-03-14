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
        Schema::table('personnels', function (Blueprint $table) {
            $table->string('keterangan_2')->nullable()->after('keterangan');
            $table->string('keterangan_3')->nullable()->after('keterangan_2');
            $table->string('keterangan_4')->nullable()->after('keterangan_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn(['keterangan_2', 'keterangan_3', 'keterangan_4']);
        });
    }
};
