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
        Schema::table('warehouse_outflows', function (Blueprint $table) {
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete()->after('outflow_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_outflows', function (Blueprint $table) {
            $table->dropForeign(['satker_id']);
            $table->dropColumn('satker_id');
        });
    }
};
