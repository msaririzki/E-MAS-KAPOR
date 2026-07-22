<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budget_package_sppm_assignments')) {
            return;
        }

        Schema::create('budget_package_sppm_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_package_id')->constrained('budget_packages')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->foreignId('original_satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->foreignId('sppm_satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['budget_package_id', 'personnel_id'], 'pkg_sppm_assignment_personnel_unique');
            $table->index(['budget_package_id', 'original_satker_id'], 'bp_sppm_orig_idx');
            $table->index(['budget_package_id', 'sppm_satker_id'], 'bp_sppm_tgt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_package_sppm_assignments');
    }
};
