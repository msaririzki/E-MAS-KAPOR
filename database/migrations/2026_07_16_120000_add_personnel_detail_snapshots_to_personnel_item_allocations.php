<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_item_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('personnel_item_allocations', 'rank_snapshot')) {
                $table->string('rank_snapshot')->nullable()->after('satker_name_snapshot');
            }

            if (! Schema::hasColumn('personnel_item_allocations', 'jabatan_snapshot')) {
                $table->string('jabatan_snapshot')->nullable()->after('rank_snapshot');
            }

            if (! Schema::hasColumn('personnel_item_allocations', 'bagian_snapshot')) {
                $table->string('bagian_snapshot')->nullable()->after('jabatan_snapshot');
            }

            if (! Schema::hasColumn('personnel_item_allocations', 'gender_snapshot')) {
                $table->string('gender_snapshot', 10)->nullable()->after('bagian_snapshot');
            }

            if (! Schema::hasColumn('personnel_item_allocations', 'personnel_type_snapshot')) {
                $table->string('personnel_type_snapshot')->nullable()->after('gender_snapshot');
            }

            if (! Schema::hasColumn('personnel_item_allocations', 'kapor_sizes_snapshot')) {
                $table->json('kapor_sizes_snapshot')->nullable()->after('personnel_type_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personnel_item_allocations', function (Blueprint $table) {
            foreach ([
                'kapor_sizes_snapshot',
                'personnel_type_snapshot',
                'gender_snapshot',
                'bagian_snapshot',
                'jabatan_snapshot',
                'rank_snapshot',
            ] as $column) {
                if (Schema::hasColumn('personnel_item_allocations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
