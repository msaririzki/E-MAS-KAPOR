<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('personnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->enum('gender', ['L', 'P']);
            $table->enum('personnel_type', ['Polri', 'PNS']);
            $table->foreignId('rank_id')->constrained('ranks');
            $table->foreignId('satker_id')->constrained('satkers');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('satker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};
