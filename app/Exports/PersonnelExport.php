<?php

namespace App\Exports;

use App\Models\Personnel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Main Export class — memisahkan personel ke 2 sheet:
 *  - Sheet 1: "Data Polri"   → personel dengan rank bertipe POLRI (bukan PNS)
 *  - Sheet 2: "Data PNS/ASN" → personel dengan rank bertipe PNS / ASN
 *
 * NRP/NIP dijaga agar leading zero tidak hilang via TYPE_STRING pada ExcelSheet.
 */
class PersonnelExport implements WithMultipleSheets
{
    protected ?array $satkerIds;
    protected string $satkerName;

    /**
     * @param  array|null  $satkerIds  Array of satker IDs to filter, null = semua.
     * @param  string  $satkerName  Nama untuk header.
     */
    public function __construct(?array $satkerIds = null, string $satkerName = 'SEMUA SATKER')
    {
        $this->satkerIds = $satkerIds;
        $this->satkerName = $satkerName;
    }

    public function sheets(): array
    {
        $query = Personnel::with(['rank', 'satker'])
            ->orderBy('satker_id')
            ->orderByRaw("COALESCE((SELECT sort_order FROM ranks WHERE ranks.id = personnels.rank_id), 999)")
            ->orderBy('full_name');

        if (!empty($this->satkerIds)) {
            $query->whereIn('satker_id', $this->satkerIds);
        }

        $all = $query->get();

        // Pisahkan berdasarkan tipe rank:
        // PNS/ASN jika rank category mengandung angka Golongan (I, II, III, IV) atau type 'pns'
        $polri = $all->filter(fn ($p) => $this->isPolri($p));
        $pns   = $all->filter(fn ($p) => !$this->isPolri($p));

        return [
            new PersonnelSheetExport($polri, $this->satkerName, 'Data Polri'),
            new PersonnelSheetExport($pns, $this->satkerName, 'Data PNS'),
        ];
    }

    /**
     * Tentukan apakah personel adalah Polri (bukan PNS).
     *
     * Berdasarkan data RankSeeder:
     * - Polri: category = 'PATI', 'PAMEN', 'PAMA', 'BINTARA'
     * - PNS:   category = 'PNS'
     * - PPPK:  category = 'PNS' (masuk sheet PNS)
     */
    protected function isPolri(Personnel $p): bool
    {
        if (!$p->rank) {
            // Tidak ada rank — cek dari field golongan personel
            $golongan = strtoupper($p->golongan ?? '');
            return $golongan !== 'PNS' && $golongan !== 'ASN';
        }

        $category = strtoupper($p->rank->category ?? '');
        return $category !== 'PNS';
    }
}
