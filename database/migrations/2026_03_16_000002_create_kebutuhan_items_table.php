<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kebutuhan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kebutuhan_id')->constrained('kebutuhans')->cascadeOnDelete();
            $table->foreignId('kapor_item_id')->constrained('kapor_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kebutuhan_id', 'kapor_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kebutuhan_items');
    }
};
