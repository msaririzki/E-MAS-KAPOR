<?php

use App\Support\GolonganNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('personnels')
            ->whereIn('personnel_type', ['PNS', 'PPPK'])
            ->whereNotNull('golongan')
            ->select(['id', 'golongan'])
            ->orderBy('id')
            ->chunkById(500, function ($personnels): void {
                foreach ($personnels as $personnel) {
                    $normalized = GolonganNormalizer::major($personnel->golongan);

                    if ($normalized !== null && $normalized !== (string) $personnel->golongan) {
                        DB::table('personnels')
                            ->where('id', $personnel->id)
                            ->update(['golongan' => $normalized]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Detail subgolongan lama tidak dapat dibentuk kembali dari angka utama.
    }
};
