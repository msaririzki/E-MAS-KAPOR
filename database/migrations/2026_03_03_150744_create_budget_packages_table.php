<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_year_id')->constrained('budget_years')->cascadeOnDelete();
            $table->string('name')->comment('Misal: Paket I, Paket II');
            $table->text('description')->nullable()->comment('Deskripsi isi paket: PAKAIAN DINAS, TOPI LAPANGAN...');
            $table->enum('status', ['draft', 'finalized', 'archived'])->default('draft');
            $table->decimal('total_budget', 18, 2)->default(0)->comment('Total anggaran dihitung otomatis');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_packages');
    }
};
