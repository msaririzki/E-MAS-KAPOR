<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_item_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_package_id')->constrained('budget_packages')->cascadeOnDelete();
            $table->foreignId('package_item_id')->constrained('package_items')->cascadeOnDelete();
            $table->foreignId('kapor_item_id')->constrained('kapor_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->year('fiscal_year');
            $table->string('allocation_status')->default('eligible');
            $table->timestamp('allocated_at')->nullable();
            $table->string('nrp_snapshot')->nullable();
            $table->string('full_name_snapshot');
            $table->string('satker_name_snapshot')->nullable();
            $table->string('kapor_item_name_snapshot');
            $table->string('item_category_snapshot')->nullable();
            $table->string('budget_package_name_snapshot');
            $table->timestamps();

            $table->unique(['package_item_id', 'user_id'], 'allocations_package_item_user_unique');
            $table->index(['user_id', 'fiscal_year']);
            $table->index(['kapor_item_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_item_allocations');
    }
};
