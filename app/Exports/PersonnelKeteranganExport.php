<?php

namespace App\Exports;

use App\Models\Personnel;
use App\Models\Satker;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PersonnelKeteranganExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $satkers = Satker::query()
            ->whereHas('personnels', fn ($query) => $query->active())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sheets = [];

        foreach ($satkers as $satker) {
            $personnels = Personnel::query()
                ->active()
                ->with(['rank:id,name,sort_order', 'satker:id,name', 'user:id,nrp_nip'])
                ->where('satker_id', $satker->id)
                ->orderByRaw("CASE WHEN personnels.personnel_type = 'Polri' THEN 0 ELSE 1 END")
                ->orderByRaw('COALESCE((SELECT sort_order FROM ranks WHERE ranks.id = personnels.rank_id), 999)')
                ->orderBy('full_name')
                ->get();

            if ($personnels->isEmpty()) {
                continue;
            }

            $sheets[] = new PersonnelKeteranganSheetExport($satker, $personnels);
        }

        return $sheets;
    }
}
