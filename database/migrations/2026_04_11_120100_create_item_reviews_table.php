<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_reviews')) {
            return;
        }

        Schema::create('item_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_item_allocation_id')->nullable()->constrained('personnel_item_allocations')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->foreignId('kapor_item_id')->constrained('kapor_items')->cascadeOnDelete();
            $table->year('fiscal_year');
            $table->string('response_status');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kapor_item_id', 'fiscal_year'], 'item_reviews_user_item_year_unique');
            $table->index(['response_status', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_reviews');
    }
};
