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
        Schema::create('warehouse_outflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_item_size_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->date('outflow_date');
            $table->string('recipient_name')->nullable();
            $table->text('reference_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_outflows');
    }
};
