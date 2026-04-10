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

    protected ?array $personnelIds;

    protected string $satkerName;

    /** @var array<string, string> */
    protected array $signatorySettings;

    /**
     * @param  array|null  $satkerIds  Array of satker IDs to filter, null = semua.
     * @param  array|null  $personnelIds  Array of personnel IDs to filter, null = tanpa filter personel spesifik.
     * @param  string  $satkerName  Nama untuk header.
     * @param  array<string, string>  $signatorySettings  Resolved signatory settings.
     */
    public function __construct(?array $satkerIds = null, string $satkerName = 'SEMUA SATKER', ?array $personnelIds = null, array $signatorySettings = [])
    {
        $this->satkerIds = $satkerIds;
        $this->satkerName = $satkerName;
        $this->personnelIds = $personnelIds;
        $this->signatorySettings = $signatorySettings;
    }

    public function sheets(): array
    {
        $query = Personnel::with(['rank', 'satker'])
            ->leftJoin('satkers', 'personnels.satker_id', '=', 'satkers.id')
            ->select('personnels.*')
            ->orderByRaw("CASE WHEN satkers.code = 'POLDA-NTB' THEN 1 ELSE 0 END ASC")
            ->orderBy('satkers.sort_order')
            ->orderBy('satkers.name')
            ->orderByRaw('COALESCE((SELECT sort_order FROM ranks WHERE ranks.id = personnels.rank_id), 999)')
            ->orderBy('personnels.full_name');

        if (! empty($this->satkerIds)) {
            $query->whereIn('satker_id', $this->satkerIds);
        }

        if ($this->personnelIds !== null) {
            $query->whereIn('personnels.id', $this->personnelIds);
        }

        $all = $query->get();

        // Pisahkan berdasarkan tipe rank:
        // PNS/ASN jika rank category mengandung angka Golongan (I, II, III, IV) atau type 'pns'
        $polri = $all->filter(fn ($p) => $this->isPolri($p));
        $pns = $all->filter(fn ($p) => ! $this->isPolri($p));

        return [
            new PersonnelSheetExport($polri, $this->satkerName, 'Data Polri', $this->signatorySettings),
            new PersonnelSheetExport($pns, $this->satkerName, 'Data PNS', $this->signatorySettings),
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
        if (! $p->rank) {
            // Tidak ada rank — cek dari field golongan personel
            $golongan = strtoupper($p->golongan ?? '');

            return $golongan !== 'PNS' && $golongan !== 'ASN';
        }

        $category = strtoupper($p->rank->category ?? '');

        return $category !== 'PNS';
    }
}
