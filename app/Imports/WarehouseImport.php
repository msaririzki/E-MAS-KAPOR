<?php

namespace App\Imports;

use App\Models\WarehouseItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehouseImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Ensure row is treated as array if it's not already
                $rowArray = is_array($row) ? $row : $row->toArray();

                // Pastikan nama barang ada
                if (empty($rowArray['nama_barang'])) {
                    continue;
                }

                $itemName = trim($rowArray['nama_barang']);
                $unit = 'PCS';
                $price = isset($rowArray['harga_satuan']) ? floatval($rowArray['harga_satuan']) : 0;

                // Set ukuran default ke "-" dan ambil kuantitas
                $sizeLabel = '-';
                $stock = isset($rowArray['kuantitas']) ? intval($rowArray['kuantitas']) : (isset($rowArray['stok']) ? intval($rowArray['stok']) : 0);

                // Update or create WarehouseItem
                $item = WarehouseItem::updateOrCreate(
                    ['name' => $itemName],
                    ['unit' => $unit, 'price' => $price]
                );

                // Jika ada data kuantitas, update or create ukurannya
                if ($stock > 0) {
                    $itemSize = $item->sizes()->where('size_label', $sizeLabel)->first();
                    if ($itemSize) {
                        $itemSize->stock += $stock;
                        $itemSize->save();
                    } else {
                        $item->sizes()->create([
                            'size_label' => $sizeLabel,
                            'stock' => $stock,
                        ]);
                    }
                }
            }
        });
    }
}
