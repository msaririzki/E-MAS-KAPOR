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
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->softDeletes();
            $table->text('deletion_reason')->nullable();
        });

        Schema::table('warehouse_item_sizes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_item_sizes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->dropColumn('deletion_reason');
            $table->dropSoftDeletes();
        });
    }
};
