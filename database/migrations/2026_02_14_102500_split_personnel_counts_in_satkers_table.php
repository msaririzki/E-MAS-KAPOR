<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::table('satkers', function (Blueprint $table) {
            $table->dropColumn('personnel_count');
            $table->integer('polri_count')->default(0)->after('sort_order');
            $table->integer('pns_count')->default(0)->after('polri_count');
        });
    }

    public function down(): void
    {
        Schema::table('satkers', function (Blueprint $table) {
            $table->integer('personnel_count')->default(0)->after('sort_order');
            $table->dropColumn(['polri_count', 'pns_count']);
        });
    }
};
