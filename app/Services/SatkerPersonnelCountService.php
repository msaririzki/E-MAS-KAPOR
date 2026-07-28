<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Satker;
use Illuminate\Support\Collection;

class SatkerPersonnelCountService
{
    public function getGlobalCounts(): array
    {
        $counts = Personnel::query()
            ->active()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN personnel_type = 'Polri' THEN 1 ELSE 0 END), 0) as polri_count,
                COALESCE(SUM(CASE WHEN personnel_type = 'PNS' THEN 1 ELSE 0 END), 0) as pns_count,
                COUNT(*) as total_personnel
            ")
            ->first();

        return [
            'polri_count' => (int) ($counts->polri_count ?? 0),
            'pns_count' => (int) ($counts->pns_count ?? 0),
            'total_personnel' => (int) ($counts->total_personnel ?? 0),
        ];
    }

    public function getCountsBySatker(): Collection
    {
        return Personnel::query()
            ->active()
            ->selectRaw("
                satker_id,
                COALESCE(SUM(CASE WHEN personnel_type = 'Polri' THEN 1 ELSE 0 END), 0) as polri_count,
                COALESCE(SUM(CASE WHEN personnel_type = 'PNS' THEN 1 ELSE 0 END), 0) as pns_count,
                COUNT(*) as total_personnel
            ")
            ->groupBy('satker_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->satker_id => [
                    'polri_count' => (int) $row->polri_count,
                    'pns_count' => (int) $row->pns_count,
                    'total_personnel' => (int) $row->total_personnel,
                ],
            ]);
    }

    public function getCountsForSatker(int $satkerId): array
    {
        return $this->getCountsBySatker()->get($satkerId, [
            'polri_count' => 0,
            'pns_count' => 0,
            'total_personnel' => 0,
        ]);
    }

    public function syncStoredCounts(): void
    {
        $countsBySatker = $this->getCountsBySatker();

        Satker::query()->get()->each(function (Satker $satker) use ($countsBySatker): void {
            $counts = $countsBySatker->get($satker->id, [
                'polri_count' => 0,
                'pns_count' => 0,
            ]);

            if (
                (int) $satker->polri_count !== (int) $counts['polri_count']
                || (int) $satker->pns_count !== (int) $counts['pns_count']
            ) {
                $satker->forceFill([
                    'polri_count' => (int) $counts['polri_count'],
                    'pns_count' => (int) $counts['pns_count'],
                ])->save();
            }
        });
    }

    public function syncStoredCountForSatker(int $satkerId): void
    {
        $satker = Satker::find($satkerId);

        if (! $satker) {
            return;
        }

        $counts = $this->getCountsForSatker($satkerId);

        $satker->forceFill([
            'polri_count' => (int) $counts['polri_count'],
            'pns_count' => (int) $counts['pns_count'],
        ])->save();
    }
}
