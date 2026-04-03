<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fiscal_year');
            $table->string('format', 20);
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('local');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_archives');
    }
};
