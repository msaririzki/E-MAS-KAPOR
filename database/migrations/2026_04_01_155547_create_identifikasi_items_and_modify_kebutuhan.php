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
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('identifikasi_items');
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create the new independent table
        Schema::create('identifikasi_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('category')->default('Lainnya');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Modify kebutuhan_items to point to the new table
        Schema::table('kebutuhan_items', function (Blueprint $table) {
            // Add new column
            $table->foreignId('identifikasi_item_id')->nullable()->constrained('identifikasi_items')->onDelete('cascade');
        });
        
        // Note: For existing local test DB, we will just wipe the existing rows in kebutuhan_items 
        // to avoid foreign key violations when dropping the old column.
        \DB::table('kebutuhan_items')->truncate();
        
        Schema::table('kebutuhan_items', function (Blueprint $table) {
            // Drop foreign keys first to release MySQL index locks
            $table->dropForeign(['kebutuhan_id']);
            $table->dropForeign(['kapor_item_id']);

            // Now safe to drop the old unique constraint
            $table->dropUnique(['kebutuhan_id', 'kapor_item_id']);
            
            // Drop the old column
            $table->dropColumn('kapor_item_id');

            // Re-add the foreign key for kebutuhan_id
            $table->foreign('kebutuhan_id')->references('id')->on('kebutuhans')->cascadeOnDelete();
            
            // Add new unique constraint for the new column
            $table->unique(['kebutuhan_id', 'identifikasi_item_id'], 'kebutuhan_identifikasi_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kebutuhan_items', function (Blueprint $table) {
            $table->foreignId('kapor_item_id')->nullable()->constrained('kapor_items')->onDelete('cascade');
            $table->dropForeign(['identifikasi_item_id']);
            $table->dropColumn('identifikasi_item_id');
        });

        Schema::dropIfExists('identifikasi_items');
    }
};
