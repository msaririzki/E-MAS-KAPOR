<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemLock
{
    /**
     * Block write actions when the system is locked by Superadmin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockMessage = self::resolveLockMessage();

        if ($lockMessage !== null && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return self::buildBlockedResponse($request, $lockMessage);
        }

        return $next($request);
    }

    public static function buildBlockedResponse(Request $request, ?string $lockMessage = null): Response
    {
        $lockMessage ??= self::resolveLockMessage() ?? 'Aksi ini tidak dapat dilakukan saat ini.';

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $lockMessage,
                'input_period' => self::resolveStatus(),
            ], 403);
        }

        return redirect()
            ->back(302, [], route('dashboard'))
            ->withInput()
            ->with('error', $lockMessage);
    }

    public static function resolveStatus(): array
    {
        $startDateStr = Setting::getValue('input_start_date', date('Y-02-01'));
        $endDateStr = Setting::getValue('input_end_date', date('Y-08-31'));
        $periodLabel = $startDateStr.' s/d '.$endDateStr;

        try {
            $startDate = Carbon::parse($startDateStr)->startOfDay();
            $endDate = Carbon::parse($endDateStr)->endOfDay();
            $now = now();
            $periodLabel = $startDate->translatedFormat('d M Y').' s/d '.$endDate->translatedFormat('d M Y');
        } catch (\Exception $e) {
            return [
                'state' => 'unknown',
                'is_open' => true,
                'title' => 'Status Periode Belum Tersedia',
                'message' => 'Jadwal input belum dapat dibaca. Silakan hubungi admin apabila Anda tidak bisa menyimpan data.',
                'period_label' => $periodLabel,
                'tone' => 'info',
            ];
        }

        $isLocked = Setting::getValue('is_system_locked', 'false');
        if ($isLocked === 'true' || $isLocked === '1') {
            return [
                'state' => 'manual_locked',
                'is_open' => false,
                'title' => 'Input Ditutup Sementara',
                'message' => 'Superadmin sedang menutup pengisian data kapor secara manual. Anda masih bisa melihat data, tetapi perubahan baru tidak dapat disimpan.',
                'period_label' => $periodLabel,
                'tone' => 'error',
            ];
        }

        if ($now->lessThan($startDate)) {
            return [
                'state' => 'scheduled',
                'is_open' => false,
                'title' => 'Periode Input Belum Dibuka',
                'message' => 'Pengisian data kapor belum dimulai. Silakan kembali saat periode input dibuka agar perubahan dapat disimpan.',
                'period_label' => $periodLabel,
                'tone' => 'info',
            ];
        }

        if ($now->greaterThan($endDate)) {
            return [
                'state' => 'closed',
                'is_open' => false,
                'title' => 'Periode Input Sudah Ditutup',
                'message' => 'Masa pengisian data kapor untuk tahun anggaran ini sudah berakhir. Data tetap bisa dilihat, namun perubahan baru tidak dapat disimpan.',
                'period_label' => $periodLabel,
                'tone' => 'error',
            ];
        }

        return [
            'state' => 'open',
            'is_open' => true,
            'title' => 'Periode Input Sedang Berjalan',
            'message' => 'Perubahan data kapor masih bisa disimpan. Pastikan data personel dan ukuran sudah lengkap sebelum periode ditutup.',
            'period_label' => $periodLabel,
            'tone' => 'success',
        ];
    }

    public static function resolveLockMessage(): ?string
    {
        $status = self::resolveStatus();

        return $status['is_open'] ? null : $status['title'].'. '.$status['message'].' Periode yang berlaku: '.$status['period_label'].'.';
    }
}
