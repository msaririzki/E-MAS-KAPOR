<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('kapor_items', function (Blueprint $table) {
            $table->id();
            $table->enum('category', [
                'Tutup_Kepala', 'Tutup_Badan', 'Tutup_Kaki', 'Atribut', 'Lainnya',
            ]);
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->enum('gender_specific', ['L', 'P'])->nullable()->comment('null = semua gender');
            $table->json('rank_categories')->nullable()->comment('Array of rank categories, e.g. ["Pamen","Pama"]');
            $table->json('unit_keywords')->nullable()->comment('Array of satker keywords, e.g. ["Lantas","Brimob"]');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kapor_items');
    }
};
