<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('kebutuhan_items', 'kapor_item_id')) {
            return;
        }

        $hasKebutuhanForeign = $this->hasForeignKey('kebutuhan_items', 'kebutuhan_items_kebutuhan_id_foreign');
        $hasKaporItemForeign = $this->hasForeignKey('kebutuhan_items', 'kebutuhan_items_kapor_item_id_foreign');
        $hasLegacyUnique = $this->hasIndex('kebutuhan_items', 'kebutuhan_items_kebutuhan_id_kapor_item_id_unique');

        Schema::table('kebutuhan_items', function (Blueprint $table) use ($hasKebutuhanForeign, $hasKaporItemForeign, $hasLegacyUnique) {
            if ($hasKebutuhanForeign) {
                $table->dropForeign('kebutuhan_items_kebutuhan_id_foreign');
            }

            if ($hasKaporItemForeign) {
                $table->dropForeign('kebutuhan_items_kapor_item_id_foreign');
            }

            if ($hasLegacyUnique) {
                $table->dropUnique('kebutuhan_items_kebutuhan_id_kapor_item_id_unique');
            }

            $table->dropColumn('kapor_item_id');
        });

        if (! $this->hasForeignKey('kebutuhan_items', 'kebutuhan_items_kebutuhan_id_foreign')) {
            Schema::table('kebutuhan_items', function (Blueprint $table) {
                $table->foreign('kebutuhan_id')->references('id')->on('kebutuhans')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // No down migration required for cleanup
    }

    private function hasForeignKey(string $tableName, string $constraintName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = ?
               AND CONSTRAINT_NAME = ?
             LIMIT 1',
            [$tableName, 'FOREIGN KEY', $constraintName]
        );

        return $result !== null;
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            [$tableName, $indexName]
        );

        return $result !== null;
    }
};
