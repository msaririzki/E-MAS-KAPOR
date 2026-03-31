<?php

namespace App\Exports;

use App\Models\Personnel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PersonnelKeteranganExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function collection(): Collection
    {
        return Personnel::query()
            ->with(['rank:id,name', 'satker:id,name', 'user:id,nrp_nip'])
            ->leftJoin('satkers', 'personnels.satker_id', '=', 'satkers.id')
            ->select('personnels.*')
            ->orderBy('satkers.sort_order')
            ->orderBy('satkers.name')
            ->orderBy('personnels.full_name')
            ->get()
            ->map(function (Personnel $personnel, int $index): array {
                return [
                    'id' => $personnel->id,
                    'no' => $index + 1,
                    'nama' => $personnel->full_name,
                    'nrp_nip' => $personnel->user?->nrp_nip ?? $personnel->nrp,
                    'satker' => $personnel->satker?->name,
                    'pangkat' => $personnel->rank?->name,
                    'golongan' => $personnel->golongan,
                    'jenis_kelamin' => $personnel->gender,
                    'agama' => $personnel->religion,
                    'jabatan' => $personnel->jabatan,
                    'bag_fungsi' => $personnel->bagian,
                    'keterangan_1' => $personnel->keterangan,
                    'keterangan_2' => $personnel->keterangan_2,
                    'keterangan_3' => $personnel->keterangan_3,
                    'keterangan_4' => $personnel->keterangan_4,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'id',
            'no',
            'nama',
            'nrp_nip',
            'satker',
            'pangkat',
            'golongan',
            'jenis_kelamin',
            'agama',
            'jabatan',
            'bag_fungsi',
            'keterangan_1',
            'keterangan_2',
            'keterangan_3',
            'keterangan_4',
        ];
    }
}
