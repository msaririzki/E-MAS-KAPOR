<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentPersonnelTemplateExport;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\StudentPersonnelImportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class StudentPersonnelImportController extends Controller
{
    private const SESSION_KEY = 'student_personnel_import_preview';

    public function __construct(private readonly StudentPersonnelImportService $importService) {}

    public function downloadTemplate()
    {
        AuditLogger::log(
            'Unduh Template Siswa Lengkap',
            'Manajemen Personil',
            null,
            null,
            null,
            'info',
            'Superadmin mengunduh template unggah siswa lengkap.',
        );

        return Excel::download(
            new StudentPersonnelTemplateExport,
            'Template_Unggah_Siswa_Lengkap.xlsx',
        );
    }

    public function import(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $validated = $request->validate([
            'satker_id' => ['required', 'integer', Rule::exists('satkers', 'id')],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
        ]);

        $payload = $this->importService->preview(
            $request->file('file'),
            (int) $validated['satker_id'],
        );

        if ((int) data_get($payload, 'stats.total', 0) === 0) {
            return back()->withInput()->withErrors([
                'file' => 'File tidak berisi data siswa. Isi data mulai baris 12 pada sheet Data Siswa.',
            ]);
        }

        $path = 'import-previews/student-personnel/'.$request->user()->id.'-'.Str::uuid().'.json';
        Storage::disk('local')->put($path, json_encode($payload, JSON_THROW_ON_ERROR));
        $this->replacePreview($request, $path);

        AuditLogger::log(
            'Pratinjau Unggah Siswa Lengkap',
            'Manajemen Personil',
            null,
            null,
            null,
            'info',
            sprintf(
                'Pratinjau siswa: %d baru, %d diperbarui, %d tetap, %d bermasalah.',
                $payload['stats']['create'],
                $payload['stats']['update'],
                $payload['stats']['no_change'],
                $payload['stats']['error'],
            ),
        );

        return redirect()->route('admin.personnel.student-import-preview');
    }

    public function preview(Request $request)
    {
        $payload = $this->previewPayload($request);
        if ($payload === null) {
            return redirect()->route('admin.personnel.index')
                ->with('error', 'Pratinjau siswa tidak ditemukan atau sudah kedaluwarsa. Unggah kembali file Excel.');
        }

        $status = $request->string('status')->toString();
        $allowedStatuses = ['create', 'update', 'no_change', 'error'];
        $filteredRows = collect($payload['rows']);
        if (in_array($status, $allowedStatuses, true)) {
            $filteredRows = $filteredRows->where('status', $status)->values();
        } else {
            $status = '';
        }

        $perPage = 100;
        $page = max(1, $request->integer('page', 1));
        $rows = new LengthAwarePaginator(
            $filteredRows->slice(($page - 1) * $perPage, $perPage)->values(),
            $filteredRows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('admin.personnel.student_import_preview', [
            'payload' => $payload,
            'rows' => $rows,
            'stats' => $payload['stats'],
            'statusFilter' => $status,
        ]);
    }

    public function confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $payload = $this->previewPayload($request);
        if ($payload === null) {
            return redirect()->route('admin.personnel.index')
                ->with('error', 'Pratinjau siswa sudah tidak tersedia. Unggah kembali file Excel.');
        }
        if ((int) data_get($payload, 'stats.error', 0) > 0) {
            return redirect()->route('admin.personnel.student-import-preview', ['status' => 'error'])
                ->with('error', 'Perbaiki seluruh baris bermasalah pada Excel, lalu unggah kembali sebelum menyimpan.');
        }

        try {
            $result = $this->importService->save(
                $payload['rows'],
                (int) $payload['satker_id'],
                $request->user()->id,
                $payload['source_file'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.personnel.student-import-preview')
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.personnel.student-import-preview')
                ->with('error', 'Data siswa belum dapat disimpan. Silakan periksa file dan coba kembali.');
        }
        $this->clearPreview($request);

        AuditLogger::log(
            'Konfirmasi Unggah Siswa Lengkap',
            'Manajemen Personil',
            null,
            null,
            $result,
            'success',
            sprintf(
                '%d siswa dibuat, %d diperbarui, %d tidak berubah. Tidak ada akun login yang dibuat.',
                $result['created'],
                $result['updated'],
                $result['unchanged'],
            ),
        );

        return redirect()->route('admin.personnel.index', ['satker_id' => $payload['satker_id']])
            ->with(
                'success',
                sprintf(
                    'Unggah siswa selesai: %d data baru, %d diperbarui, dan %d tidak berubah. Siswa dibuat tanpa akun login.',
                    $result['created'],
                    $result['updated'],
                    $result['unchanged'],
                ),
            );
    }

    public function cancel(Request $request)
    {
        $this->clearPreview($request);

        return redirect()->route('admin.personnel.index')->with('info', 'Pratinjau unggah siswa dibatalkan.');
    }

    private function previewPayload(Request $request): ?array
    {
        $path = $request->session()->get(self::SESSION_KEY);
        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $payload = json_decode(Storage::disk('local')->get($path), true);

        return is_array($payload) ? $payload : null;
    }

    private function replacePreview(Request $request, string $path): void
    {
        $oldPath = $request->session()->get(self::SESSION_KEY);
        if (is_string($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }
        $request->session()->put(self::SESSION_KEY, $path);
    }

    private function clearPreview(Request $request): void
    {
        $path = $request->session()->pull(self::SESSION_KEY);
        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
