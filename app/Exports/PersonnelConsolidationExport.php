<?php

namespace App\Exports;

use App\Models\Personnel;
use App\Models\Satker;
use App\Models\Setting;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PersonnelConsolidationExport implements WithMultipleSheets
{
    public function __construct(private readonly Satker $satker) {}

    public function sheets(): array
    {
        $personnels = Personnel::query()
            ->with('rank')
            ->where('satker_id', $this->satker->id)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN NULLIF(TRIM(bagian), '') IS NULL THEN 1 ELSE 0 END")
            ->orderBy('bagian')
            ->orderByRaw('COALESCE((SELECT sort_order FROM ranks WHERE ranks.id = personnels.rank_id), 999)')
            ->orderBy('full_name')
            ->get();

        $polri = $personnels->where('personnel_type', 'Polri')->values();
        $pns = $personnels->where('personnel_type', 'PNS')->values();
        $year = (string) Setting::getValue('fiscal_year', date('Y'));

        return [
            new PersonnelConsolidationDataSheet($polri, $this->satker, 'Data Polri', $year),
            new PersonnelConsolidationDataSheet($pns, $this->satker, 'Data PNS', $year),
            new PersonnelConsolidationInstructionSheet($this->satker, $year),
        ];
    }
}
