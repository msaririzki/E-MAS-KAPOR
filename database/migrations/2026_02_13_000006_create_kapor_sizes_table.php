<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('kapor_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapor_item_id')->constrained()->cascadeOnDelete();
            $table->string('size_label'); // S, M, L, XL, 39, 40, 41, etc.
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kapor_sizes');
    }
};
