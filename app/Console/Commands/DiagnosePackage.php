<?php

namespace App\Console\Commands;

use App\Models\BudgetPackage;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosePackage extends Command
{
    protected $signature   = 'kapor:diagnose {package_id? : ID paket (kosong = semua)} {--fix : Recalculate dan simpan}';
    protected $description = 'Diagnosa dan sinkronkan jumlah penerima paket kapor dengan data personel terkini';

    public function handle(): int
    {
        $pkgId    = $this->argument('package_id');
        $shouldFix = $this->option('fix');

        $query = BudgetPackage::with(['items.kaporItem', 'items.recipients.satker']);
        if ($pkgId) {
            $query->where('id', $pkgId);
        }
        $packages = $query->get();

        if ($packages->isEmpty()) {
            $this->error("Tidak ada paket ditemukan.");
            return 1;
        }

        foreach ($packages as $pkg) {
            $this->newLine();
            $this->line("═══════════════════════════════════════════════════════");
            $this->info("PAKET [{$pkg->id}]: {$pkg->name}");
            $this->line("═══════════════════════════════════════════════════════");

            $headers = ['Item', 'Cached QTY', 'Realtime Count', 'Selisih', 'Status'];
            $rows    = [];
            $hasIssue = false;

            foreach ($pkg->items as $item) {
                $realtimeTotal = 0;
                $itemName   = $item->kaporItem->item_name ?? '';
                $autoGender = PackageItemRecipient::detectGenderFromItemName($itemName);

                foreach ($item->recipients as $r) {
                    $f = $r->recipient_filters ?? [];
                    $q = Personnel::where('satker_id', $r->satker_id)->where('is_active', true);

                    if (! empty($f['personnel_type'])) {
                        $mt = array_map(fn ($t) => match(strtolower($t)) {
                            'polri'=>'Polri','pns'=>'PNS','pppk'=>'PPPK',default=>$t}, $f['personnel_type']);
                        $q->whereIn('personnel_type', $mt);
                    }
                    if (! empty($f['gender'])) {
                        $q->whereIn('gender', $f['gender']);
                    } elseif ($autoGender !== null) {
                        $q->where('gender', $autoGender);
                    }
                    if (! empty($f['rank_categories'])) $q->whereHas('rank', fn($rq) => $rq->whereIn('category', $f['rank_categories']));
                    if (! empty($f['keterangan']))      $q->whereIn('keterangan', $f['keterangan']);
                    if (! empty($f['golongan']))        $q->whereIn('golongan', $f['golongan']);
                    $realtimeTotal += $q->count();
                }

                $cached  = (int) $item->calculated_qty;
                $diff    = $realtimeTotal - $cached;
                $status  = ($diff === 0) ? '✅ OK' : "⚠️  BEDA ({$diff})";
                if ($diff !== 0) $hasIssue = true;

                $rows[] = [
                    substr($item->kaporItem->item_name, 0, 35),
                    $cached,
                    $realtimeTotal,
                    $diff,
                    $status,
                ];
            }

            $this->table($headers, $rows);

            if ($hasIssue) {
                if ($shouldFix) {
                    $this->warn("  ↳ Memperbaiki...");
                    DB::transaction(function () use ($pkg) {
                    foreach ($pkg->items as $item) {
                        $totalQty   = 0;
                        $itemName   = $item->kaporItem->item_name ?? '';
                        $autoGender = PackageItemRecipient::detectGenderFromItemName($itemName);

                        foreach ($item->recipients as $r) {
                            $f = $r->recipient_filters ?? [];
                            $q = Personnel::where('satker_id', $r->satker_id)->where('is_active', true);
                            if (! empty($f['personnel_type'])) {
                                $mt = array_map(fn ($t) => match(strtolower($t)) {'polri'=>'Polri','pns'=>'PNS','pppk'=>'PPPK',default=>$t}, $f['personnel_type']);
                                $q->whereIn('personnel_type', $mt);
                            }
                            if (! empty($f['gender'])) {
                                $q->whereIn('gender', $f['gender']);
                            } elseif ($autoGender !== null) {
                                $q->where('gender', $autoGender);
                            }
                            if (! empty($f['rank_categories'])) $q->whereHas('rank', fn($rq) => $rq->whereIn('category', $f['rank_categories']));
                            if (! empty($f['keterangan']))      $q->whereIn('keterangan', $f['keterangan']);
                            if (! empty($f['golongan']))        $q->whereIn('golongan', $f['golongan']);
                            $count = $q->count();
                            $r->update(['matched_count' => $count]);
                            $totalQty += $count;
                        }
                        $price = (float) ($item->custom_price ?? $item->kaporItem->price ?? 0);
                        $item->update(['calculated_qty' => $totalQty, 'calculated_total' => $totalQty * $price]);
                    }
                    $pkg->update(['total_budget' => $pkg->items()->sum('calculated_total')]);
                });
                    $this->info("  ✅ Paket [{$pkg->id}] berhasil disinkronkan.");
                } else {
                    $this->warn("  ↳ Ada perbedaan. Jalankan dengan --fix untuk memperbaiki.");
                }
            } else {
                $this->info("  ✅ Semua item sudah sinkron.");
            }
        }

        $this->newLine();
        $this->info("Selesai. Gunakan --fix untuk memperbaiki semua perbedaan.");
        return 0;
    }
}
