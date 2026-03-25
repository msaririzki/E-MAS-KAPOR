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
                // Pastikan nama barang ada
                if (empty($row['nama_barang'])) {
                    continue;
                }

                $itemName = trim($row['nama_barang']);
                $unit = isset($row['satuan']) ? trim($row['satuan']) : 'PCS';
                $price = isset($row['harga_satuan']) ? floatval($row['harga_satuan']) : 0;

                // Ambil ukuran dan kuantitas (di Excel mungkin bernama "ukuran" dan "kuantitas" atau "stok")
                $sizeLabel = isset($row['ukuran']) ? trim($row['ukuran']) : null;
                $stock = isset($row['kuantitas']) ? intval($row['kuantitas']) : (isset($row['stok']) ? intval($row['stok']) : 0);

                // Update or create WarehouseItem
                $item = WarehouseItem::updateOrCreate(
                    ['name' => $itemName],
                    ['unit' => $unit, 'price' => $price]
                );

                // Jika ada data ukuran, update or create ukurannya
                if ($sizeLabel) {
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
