<?php

namespace App\Exports;

use App\Models\WarehouseItem;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class WarehouseExport extends DefaultValueBinder implements FromView, ShouldAutoSize, WithCustomValueBinder
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    public function view(): View
    {
        $query = WarehouseItem::withSum('sizes', 'stock');

        if (! empty($this->filters['search'])) {
            $searchTerm = $this->filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('unit', 'like', "%{$searchTerm}%")
                    ->orWhere('tahun', 'like', "%{$searchTerm}%")
                    ->orWhere('sumber_pengadaan', 'like', "%{$searchTerm}%")
                    ->orWhere('kategori_stok', 'like', "%{$searchTerm}%")
                    ->orWhere('price', 'like', "%{$searchTerm}%");
            });
        }
        if (! empty($this->filters['sumber_pengadaan'])) {
            $query->where('sumber_pengadaan', $this->filters['sumber_pengadaan']);
        }
        if (! empty($this->filters['tahun'])) {
            $query->where('tahun', $this->filters['tahun']);
        }
        if (! empty($this->filters['kategori_stok'])) {
            $query->where('kategori_stok', $this->filters['kategori_stok']);
        }

        $items = $query->orderBy('name', 'asc')->get();

        return view('admin.warehouse.export_excel', [
            'items' => $items,
            'is_excel' => true,
            'kategori_stok' => $this->filters['kategori_stok'] ?? null,
        ]);
    }
}
