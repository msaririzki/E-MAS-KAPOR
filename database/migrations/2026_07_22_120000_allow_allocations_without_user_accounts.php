<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->foreignExists('personnel_item_allocations_user_id_foreign')) {
            Schema::table('personnel_item_allocations', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }
        if (! $this->indexExists('allocations_package_item_index')) {
            Schema::table('personnel_item_allocations', function (Blueprint $table) {
                $table->index('package_item_id', 'allocations_package_item_index');
            });
        }
        if ($this->indexExists('allocations_package_item_user_unique')) {
            Schema::table('personnel_item_allocations', function (Blueprint $table) {
                $table->dropUnique('allocations_package_item_user_unique');
            });
        }
        Schema::table('personnel_item_allocations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
        Schema::table('personnel_item_allocations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            if (! $this->indexExists('allocations_package_item_personnel_unique')) {
                $table->unique(['package_item_id', 'personnel_id'], 'allocations_package_item_personnel_unique');
            }
        });
    }

    public function down(): void
    {
        DB::table('personnel_item_allocations')->whereNull('user_id')->delete();

        if ($this->foreignExists('personnel_item_allocations_user_id_foreign')) {
            Schema::table('personnel_item_allocations', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }
        if ($this->indexExists('allocations_package_item_personnel_unique')) {
            Schema::table('personnel_item_allocations', function (Blueprint $table) {
                $table->dropUnique('allocations_package_item_personnel_unique');
            });
        }
        Schema::table('personnel_item_allocations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
        Schema::table('personnel_item_allocations', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['package_item_id', 'user_id'], 'allocations_package_item_user_unique');
        });
        if ($this->indexExists('allocations_package_item_index')) {
            Schema::table('personnel_item_allocations', function (Blueprint $table) {
                $table->dropIndex('allocations_package_item_index');
            });
        }
    }

    private function foreignExists(string $name): bool
    {
        return collect(Schema::getForeignKeys('personnel_item_allocations'))->contains('name', $name);
    }

    private function indexExists(string $name): bool
    {
        return collect(Schema::getIndexes('personnel_item_allocations'))->contains('name', $name);
    }
};
