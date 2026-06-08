<?php

namespace App\Services;

use App\Models\IdentifikasiItem;
use App\Models\Satker;
use Illuminate\Support\Collection;

class KebutuhanEligibilityService
{
    private const CATEGORY_ORDER_SQL = "CASE
        WHEN category = 'Tutup_Kepala' THEN 1
        WHEN category = 'Tutup_Badan' THEN 2
        WHEN category = 'Tutup_Kaki' THEN 3
        ELSE 999 END";

    /**
     * @return Collection<int, IdentifikasiItem>
     */
    public function eligibleItemsForSatker(?Satker $satker): Collection
    {
        return IdentifikasiItem::query()
            ->where('is_active', true)
            ->orderByRaw(self::CATEGORY_ORDER_SQL)
            ->orderBy('item_name')
            ->get()
            ->values();
    }

    public function canSatkerChooseItem(?Satker $satker, IdentifikasiItem $item): bool
    {
        return $item->is_active;
    }

    public function eligibleSatkerCountForItem(IdentifikasiItem $item): ?int
    {
        return match ($this->itemGroup($item)) {
            'lantas', 'polair', 'tik', 'humas' => 11,
            'reskrim' => 17,
            'primod' => 1,
            default => null,
        };
    }

    public function itemGroup(IdentifikasiItem $item): ?string
    {
        $name = strtoupper($item->item_name);

        if ($this->itemMatches($name, ['BRIMOB', 'PRIMOD', 'PROVOS', 'PROVOST'])) {
            return 'primod';
        }

        if ($this->itemMatches($name, ['LANTAS', 'POLANTAS'])) {
            return 'lantas';
        }

        if ($this->itemMatches($name, ['POLAIR', 'AIRUD'])) {
            return 'polair';
        }

        if ($this->itemMatches($name, ['RESKRIM', 'RESINTEL', 'RESINTELPAM', 'TACTICAL RESINTEL'])) {
            return 'reskrim';
        }

        if ($this->itemMatches($name, ['TIK'])) {
            return 'tik';
        }

        if ($this->itemMatches($name, ['HUMAS'])) {
            return 'humas';
        }

        return null;
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function itemMatches(string $name, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($this->containsToken($name, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function containsToken(string $value, string $needle): bool
    {
        if (in_array($needle, ['TIK', 'PPA', 'PPO'], true)) {
            return (bool) preg_match('/(^|[^A-Z0-9])'.preg_quote($needle, '/').'([^A-Z0-9]|$)/', $value);
        }

        return str_contains($value, $needle);
    }
}
