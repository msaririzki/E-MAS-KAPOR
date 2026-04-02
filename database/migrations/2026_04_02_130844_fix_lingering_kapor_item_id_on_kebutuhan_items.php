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
        if (\Illuminate\Support\Facades\Schema::hasColumn('kebutuhan_items', 'kapor_item_id')) {
            \Illuminate\Support\Facades\Schema::table('kebutuhan_items', function (Blueprint $table) {
                try {
                    $table->dropForeign(['kebutuhan_id']);
                } catch (\Exception $e) {}

                try {
                    $table->dropForeign(['kapor_item_id']);
                } catch (\Exception $e) {}

                try {
                    $table->dropUnique(['kebutuhan_id', 'kapor_item_id']);
                } catch (\Exception $e) {}
                
                $table->dropColumn('kapor_item_id');
                
                $table->foreign('kebutuhan_id')->references('id')->on('kebutuhans')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // No down migration required for cleanup
    }
};
