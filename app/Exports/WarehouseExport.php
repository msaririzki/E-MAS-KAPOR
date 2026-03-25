<?php

namespace App\Exports;

use App\Models\WarehouseItem;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class WarehouseExport implements FromView, ShouldAutoSize
{
    public function view(): View
    {
        $items = WarehouseItem::withSum('sizes', 'stock')->orderBy('name')->get();

        return view('admin.warehouse.export_excel', [
            'items' => $items,
            'is_excel' => true,
        ]);
    }
}
