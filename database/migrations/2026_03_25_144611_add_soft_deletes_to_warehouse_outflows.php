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
            $table->softDeletes();
            $table->text('deletion_reason')->nullable()->after('reference_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_outflows', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('deletion_reason');
        });
    }
};
