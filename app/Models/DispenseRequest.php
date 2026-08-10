<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DispenseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getParsedItemsAttribute()
    {
        $payload = $this->payload;
        $method = $payload['dispense_method'] ?? 'method_1';
        $items = [];
        
        if ($method === 'method_1') {
            $satker = \App\Models\Satker::find($payload['satker_id'] ?? 0);
            $satkerName = $satker ? $satker->name : '-';
            
            foreach ($payload['items'] ?? [] as $itemData) {
                foreach ($itemData['sizes'] ?? [] as $sizeData) {
                    $size = \App\Models\WarehouseItemSize::with('item')->find($sizeData['warehouse_item_size_id'] ?? 0);
                    if ($size && $sizeData['quantity'] > 0) {
                        $items[] = [
                            'tujuan' => $satkerName,
                            'barang' => $size->item->name ?? '-',
                            'ukuran' => $size->size_label ?? '-',
                            'jumlah' => $sizeData['quantity']
                        ];
                    }
                }
            }
        } else {
            $mode = $payload['m2_mode'] ?? 'acak';
            
            $selectedItems = $payload['selected_items'] ?? [];
            foreach ($selectedItems as $itemId) {
                $item = \App\Models\WarehouseItem::find($itemId);
                $itemName = $item ? $item->name : '-';
                
                foreach ($payload['selected_satkers'] ?? [] as $satkerId) {
                    $satker = \App\Models\Satker::find($satkerId);
                    $satkerName = $satker ? $satker->name : '-';
                    
                    if ($mode === 'ukuran') {
                        $sizes = $payload['quantities_size'][$satkerId][$itemId] ?? [];
                        foreach ($sizes as $sizeId => $qty) {
                            if ($qty > 0) {
                                $sizeObj = \App\Models\WarehouseItemSize::find($sizeId);
                                $sizeLabel = $sizeObj ? $sizeObj->size_label : '-';
                                $items[] = [
                                    'tujuan' => $satkerName,
                                    'barang' => $itemName,
                                    'ukuran' => $sizeLabel,
                                    'jumlah' => $qty
                                ];
                            }
                        }
                    } else {
                        $qty = $payload['quantities'][$satkerId][$itemId] ?? 0;
                        if ($qty > 0) {
                            $items[] = [
                                'tujuan' => $satkerName,
                                'barang' => $itemName,
                                'ukuran' => 'Acak',
                                'jumlah' => $qty
                            ];
                        }
                    }
                }
            }
        }
        
        return $items;
    }
}
