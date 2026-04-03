<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnualArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function annualArchives()
    {
        $archives = AnnualArchive::query()
            ->with('generator:id,name')
            ->orderByDesc('fiscal_year')
            ->orderBy('format')
            ->get()
            ->groupBy('fiscal_year');

        return view('admin.reports.annual_archives', compact('archives'));
    }

    public function downloadAnnualArchive(AnnualArchive $annualArchive)
    {
        abort_unless(Storage::disk($annualArchive->disk)->exists($annualArchive->file_path), 404, 'File arsip tidak ditemukan.');

        return Storage::disk($annualArchive->disk)->download($annualArchive->file_path, $annualArchive->file_name);
    }

    public function export(Request $request)
    {
        $type = $request->get('type');

        return match ($type) {
            'personil-satker' => $this->exportPersonilSatker(),
            'ukuran-kapor' => $this->exportUkuranKapor(),
            'anggaran-paket' => $this->exportAnggaranPaket(),
            'personil-belum-lengkap' => $this->exportPersonilBelumLengkap(),
            'audit-log' => $this->exportAuditLog(),
            default => redirect()->back()->with('error', 'Jenis laporan tidak dikenali.'),
        };
    }

    private function exportPersonilSatker()
    {
        return Excel::download(new \App\Exports\ReportPersonnelSummaryExport, 'Rekap_Personil_Per_Satker_'.date('Y-m-d').'.xlsx');
    }

    private function exportUkuranKapor()
    {
        return Excel::download(new \App\Exports\ReportKaporSizeExport, 'Rekap_Ukuran_KAPOR_'.date('Y-m-d').'.xlsx');
    }

    private function exportAnggaranPaket()
    {
        return Excel::download(new \App\Exports\ReportBudgetPackageExport, 'Rekap_Anggaran_Paket_'.date('Y-m-d').'.xlsx');
    }

    private function exportPersonilBelumLengkap()
    {
        return Excel::download(new \App\Exports\ReportIncompletePersonnelExport, 'Data_Personil_Belum_Lengkap_'.date('Y-m-d').'.xlsx');
    }

    private function exportAuditLog()
    {
        return Excel::download(new \App\Exports\ReportAuditLogExport, 'Riwayat_Audit_Log_'.date('Y-m-d').'.xlsx');
    }
}
