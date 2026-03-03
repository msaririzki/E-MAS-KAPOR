<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_item_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_item_id')->constrained('package_items')->cascadeOnDelete();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->json('recipient_filters')->nullable()
                ->comment('Filter: {"personnel_type":["polri"],"gender":["L"],"rank_categories":["PAMEN"]}');
            $table->integer('matched_count')->default(0)->comment('Jumlah personel yang cocok');
            $table->timestamps();

            $table->unique(['package_item_id', 'satker_id'], 'pkg_item_satker_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_item_recipients');
    }
};
