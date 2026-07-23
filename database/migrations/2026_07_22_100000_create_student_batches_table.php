<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->year('fiscal_year');
            $table->foreignId('satker_id')->constrained('satkers')->restrictOnDelete();
            $table->string('procurement_group', 30);
            $table->unsignedInteger('requested_male_count')->default(0);
            $table->unsignedInteger('requested_female_count')->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fiscal_year', 'status']);
            $table->index(['satker_id', 'procurement_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_batches');
    }
};
