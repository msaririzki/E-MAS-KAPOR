<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentPersonnelTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new StudentPersonnelTemplateDataSheet,
            new StudentPersonnelTemplateInstructionSheet,
            new StudentPersonnelTemplateRankSheet,
            new StudentPersonnelTemplateSizeSheet,
        ];
    }
}
