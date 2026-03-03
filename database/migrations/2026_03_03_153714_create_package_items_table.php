<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_package_id')->constrained('budget_packages')->cascadeOnDelete();
            $table->foreignId('kapor_item_id')->constrained('kapor_items')->cascadeOnDelete();
            $table->decimal('custom_price', 15, 2)->nullable()
                ->comment('Override harga jika berbeda dari harga global item');
            $table->integer('calculated_qty')->default(0)->comment('Total quantity penerima (dihitung otomatis)');
            $table->decimal('calculated_total', 18, 2)->default(0)->comment('Total biaya (qty × price)');
            $table->timestamps();

            $table->unique(['budget_package_id', 'kapor_item_id'], 'pkg_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_items');
    }
};
