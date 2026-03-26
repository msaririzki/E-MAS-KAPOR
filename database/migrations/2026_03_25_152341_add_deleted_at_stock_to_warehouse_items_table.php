<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->integer('deleted_at_stock')->nullable()->after('deletion_reason');
        });

        // Populate existing trashed items with a best-effort sum
        $trashedItems = DB::table('warehouse_items')->whereNotNull('deleted_at')->get();
        foreach ($trashedItems as $item) {
            $sum = DB::table('warehouse_item_sizes')
                ->where('warehouse_item_id', $item->id)
                ->whereNotNull('deleted_at')
                ->sum('stock');
            
            DB::table('warehouse_items')->where('id', $item->id)->update([
                'deleted_at_stock' => $sum
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->dropColumn('deleted_at_stock');
        });
    }
};
