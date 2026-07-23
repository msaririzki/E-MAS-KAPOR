<?php

namespace App\Exports;

use App\Models\StudentBatch;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentBatchExport implements WithMultipleSheets
{
    public function __construct(private readonly StudentBatch $batch) {}

    public function sheets(): array
    {
        return [
            new StudentBatchDataSheet($this->batch),
            new StudentBatchInstructionSheet,
            new StudentBatchSizeReferenceSheet,
            new StudentBatchRankReferenceSheet,
        ];
    }
}
