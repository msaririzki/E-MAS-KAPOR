<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdm_satker_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['satker_id', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdm_satker_aliases');
    }
};
