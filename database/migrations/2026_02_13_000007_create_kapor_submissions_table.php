<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('kapor_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->foreignId('kapor_item_id')->constrained('kapor_items');
            $table->foreignId('kapor_size_id')->constrained('kapor_sizes');
            $table->year('fiscal_year');
            $table->timestamps();

            // Ensure one submission per personnel per item per fiscal year
            $table->unique(
            ['personnel_id', 'kapor_item_id', 'fiscal_year'],
                'unique_submission_per_year'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kapor_submissions');
    }
};
