<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->uuid('sync_token')->nullable()->after('id');
        });

        DB::table('personnels')
            ->select('id')
            ->whereNull('sync_token')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('personnels')
                        ->where('id', $row->id)
                        ->update(['sync_token' => (string) Str::uuid()]);
                }
            });

        Schema::table('personnels', function (Blueprint $table) {
            $table->unique('sync_token');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropUnique('personnels_sync_token_unique');
            $table->dropColumn('sync_token');
        });
    }
};
