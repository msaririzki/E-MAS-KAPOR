<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StudentPersonnelRowsImport implements ToCollection, WithStartRow
{
    public function collection(Collection $collection): void {}

    public function startRow(): int
    {
        return 11;
    }
}
