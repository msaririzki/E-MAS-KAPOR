<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriodGate
{
    public static function resolveSatkerStatus(): array
    {
        $isLocked = Setting::getValue('is_satker_locked', 'false');

        if ($isLocked === 'true' || $isLocked === '1') {
            return [
                'state' => 'manual_locked',
                'is_open' => false,
                'title' => 'Data Satker Dikunci',
                'message' => 'Superadmin sedang mengunci perubahan data personel dan data satker. Data tetap bisa dilihat, tetapi perubahan baru tidak dapat disimpan.',
                'period_label' => 'Mode kunci global aktif',
                'tone' => 'error',
            ];
        }

        return [
            'state' => 'open',
            'is_open' => true,
            'title' => 'Data Satker Terbuka',
            'message' => 'Perubahan data personel dan data satker dapat disimpan.',
            'period_label' => 'Mode kunci global nonaktif',
            'tone' => 'success',
        ];
    }

    public static function resolveSatkerLockMessage(): ?string
    {
        $status = self::resolveSatkerStatus();

        return $status['is_open'] ? null : $status['title'].'. '.$status['message'].' Status saat ini: '.$status['period_label'].'.';
    }

    public static function resolveInputStatus(): array
    {
        $fiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));

        return self::resolveStatus(
            startKey: 'input_start_date',
            endKey: 'input_end_date',
            defaultStart: $fiscalYear.'-02-01',
            defaultEnd: $fiscalYear.'-08-31',
            openTitle: 'Periode Input Sedang Berjalan',
            openMessage: 'Perubahan data kapor masih bisa disimpan. Pastikan data personel dan ukuran sudah lengkap sebelum periode ditutup.',
            scheduledTitle: 'Periode Input Belum Dibuka',
            scheduledMessage: 'Pengisian data kapor belum dimulai. Silakan kembali saat periode input dibuka agar perubahan dapat disimpan.',
            closedTitle: 'Periode Input Sudah Ditutup',
            closedMessage: 'Masa pengisian data kapor untuk tahun anggaran ini sudah berakhir. Data tetap bisa dilihat, namun perubahan baru tidak dapat disimpan.',
            manualTitle: 'Input Ditutup Sementara',
            manualMessage: 'Superadmin sedang menutup pengisian data kapor secara manual. Anda masih bisa melihat data, tetapi perubahan baru tidak dapat disimpan.',
        );
    }

    public static function resolveReviewStatus(): array
    {
        $fiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));

        return self::resolveStatus(
            startKey: 'review_start_date',
            endKey: 'review_end_date',
            defaultStart: $fiscalYear.'-10-01',
            defaultEnd: $fiscalYear.'-12-31',
            openTitle: 'Periode Review Sedang Berjalan',
            openMessage: 'Masukan review item kapor sedang dibuka. Anda dapat melaporkan item belum diterima atau memperbarui review selama periode ini berlangsung.',
            scheduledTitle: 'Periode Review Belum Dibuka',
            scheduledMessage: 'Review item kapor belum dimulai. Anda masih bisa melihat daftar item, tetapi pengiriman review baru menunggu jadwal yang ditetapkan.',
            closedTitle: 'Periode Review Sudah Ditutup',
            closedMessage: 'Periode review item kapor untuk tahun anggaran ini sudah selesai. Riwayat review tetap bisa dilihat, tetapi pengiriman baru tidak dapat dilakukan.',
            manualTitle: 'Review Ditutup Sementara',
            manualMessage: 'Superadmin sedang menutup periode review secara manual. Riwayat masih bisa dilihat, tetapi review baru belum dapat dikirim.',
            manualLockKey: 'is_review_locked',
        );
    }

    public static function resolveInputLockMessage(): ?string
    {
        $status = self::resolveInputStatus();

        return $status['is_open'] ? null : $status['title'].'. '.$status['message'].' Periode yang berlaku: '.$status['period_label'].'.';
    }

    public static function resolveReviewLockMessage(): ?string
    {
        $status = self::resolveReviewStatus();

        return $status['is_open'] ? null : $status['title'].'. '.$status['message'].' Periode yang berlaku: '.$status['period_label'].'.';
    }

    public static function buildBlockedResponse(Request $request, string $message, array $status): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $message,
                'period' => $status,
            ], 403);
        }

        return redirect()
            ->back(302, [], route('dashboard'))
            ->withInput()
            ->with('error', $message);
    }

    private static function resolveStatus(
        string $startKey,
        string $endKey,
        string $defaultStart,
        string $defaultEnd,
        string $openTitle,
        string $openMessage,
        string $scheduledTitle,
        string $scheduledMessage,
        string $closedTitle,
        string $closedMessage,
        string $manualTitle,
        string $manualMessage,
        string $manualLockKey = 'is_system_locked',
    ): array {
        $startDateStr = Setting::getValue($startKey, $defaultStart);
        $endDateStr = Setting::getValue($endKey, $defaultEnd);
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
                'message' => 'Jadwal periode belum dapat dibaca. Silakan hubungi admin apabila Anda tidak bisa menyimpan data atau mengirim review.',
                'period_label' => $periodLabel,
                'tone' => 'info',
            ];
        }

        $isLocked = Setting::getValue($manualLockKey, 'false');
        if ($isLocked === 'true' || $isLocked === '1') {
            return [
                'state' => 'manual_locked',
                'is_open' => false,
                'title' => $manualTitle,
                'message' => $manualMessage,
                'period_label' => $periodLabel,
                'tone' => 'error',
            ];
        }

        if ($now->lessThan($startDate)) {
            return [
                'state' => 'scheduled',
                'is_open' => false,
                'title' => $scheduledTitle,
                'message' => $scheduledMessage,
                'period_label' => $periodLabel,
                'tone' => 'info',
            ];
        }

        if ($now->greaterThan($endDate)) {
            return [
                'state' => 'closed',
                'is_open' => false,
                'title' => $closedTitle,
                'message' => $closedMessage,
                'period_label' => $periodLabel,
                'tone' => 'error',
            ];
        }

        return [
            'state' => 'open',
            'is_open' => true,
            'title' => $openTitle,
            'message' => $openMessage,
            'period_label' => $periodLabel,
            'tone' => 'success',
        ];
    }
}
