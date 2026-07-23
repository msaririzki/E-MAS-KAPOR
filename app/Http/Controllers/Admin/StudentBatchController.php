<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentBatchExport;
use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\StudentBatch;
use App\Services\AuditLogger;
use App\Services\StudentBatchImportService;
use App\Services\StudentBatchService;
use App\Services\StudentSizeDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StudentBatchController extends Controller
{
    public function __construct(
        private readonly StudentBatchService $studentBatchService,
        private readonly StudentBatchImportService $importService,
        private readonly StudentSizeDistributionService $sizeDistributionService,
    ) {}

    public function index(Request $request)
    {
        $query = StudentBatch::query()
            ->with('satker:id,name')
            ->withCount([
                'students',
                'students as male_count' => fn ($query) => $query->where('gender', 'L'),
                'students as female_count' => fn ($query) => $query->where('gender', 'P'),
                'students as sized_count' => fn ($query) => $query->whereNotNull('kapor_sizes'),
            ]);

        if ($request->filled('year')) {
            $query->where('fiscal_year', $request->integer('year'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $search)->orWhere('code', 'like', $search));
        }

        $batches = $query->latest('fiscal_year')->latest('id')->paginate(12)->withQueryString();
        $availableYears = StudentBatch::query()->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year');
        $summary = [
            'batches' => StudentBatch::query()->where('status', StudentBatch::STATUS_ACTIVE)->count(),
            'students' => Personnel::query()->whereNotNull('student_batch_id')->where('is_active', true)->count(),
            'male' => Personnel::query()->whereNotNull('student_batch_id')->where('is_active', true)->where('gender', 'L')->count(),
            'female' => Personnel::query()->whereNotNull('student_batch_id')->where('is_active', true)->where('gender', 'P')->count(),
        ];
        $ranks = Rank::query()->where('category', '!=', 'PNS')->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'category']);

        return view('admin.students.index', compact('availableYears', 'batches', 'ranks', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fiscal_year' => ['required', 'integer', 'between:2020,2100'],
            'procurement_group' => ['required', Rule::in(array_keys(StudentBatch::GROUPS))],
            'default_rank_id' => ['required', Rule::exists('ranks', 'id')->where(fn ($query) => $query->where('category', '!=', 'PNS'))],
            'default_jabatan' => ['required', 'string', 'max:255'],
            'default_bagian' => ['required', 'string', 'max:255'],
            'male_count' => ['required', 'integer', 'min:0', 'max:10000'],
            'female_count' => ['required', 'integer', 'min:0', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (((int) $validated['male_count'] + (int) $validated['female_count']) < 1) {
            return back()->withInput()->withErrors(['male_count' => 'Jumlah siswa pria dan wanita tidak boleh sama-sama nol.']);
        }
        if (((int) $validated['male_count'] + (int) $validated['female_count']) > 10000) {
            return back()->withInput()->withErrors(['male_count' => 'Maksimal 10.000 siswa dalam satu angkatan.']);
        }

        $batch = $this->studentBatchService->create($validated, $request->user()->id);
        AuditLogger::log('Membuat angkatan siswa '.$batch->code, 'Manajemen Siswa', $batch, null, $batch->toArray(), 'success', $batch->students()->count().' data siswa non-login dibuat.');

        return redirect()->route('admin.students.show', $batch)->with('success', 'Angkatan dan data siswa berhasil dibuat.');
    }

    public function show(Request $request, StudentBatch $studentBatch)
    {
        $studentsQuery = $studentBatch->students()->with('rank:id,name')->orderBy('student_code');
        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $studentsQuery->where(fn ($query) => $query
                ->where('full_name', 'like', $search)
                ->orWhere('nrp', 'like', $search)
                ->orWhere('student_code', 'like', $search));
        }
        if ($request->filled('gender')) {
            $studentsQuery->where('gender', $request->input('gender'));
        }
        if ($request->filled('size_status')) {
            $request->input('size_status') === 'filled'
                ? $studentsQuery->whereNotNull('kapor_sizes')
                : $studentsQuery->whereNull('kapor_sizes');
        }

        $students = $studentsQuery->paginate(30)->withQueryString();
        $studentBatch->load(['satker:id,name', 'defaultRank:id,name,category']);
        $metrics = [
            'total' => $studentBatch->students()->count(),
            'male' => $studentBatch->students()->where('gender', 'L')->count(),
            'female' => $studentBatch->students()->where('gender', 'P')->count(),
            'sized' => $studentBatch->students()->whereNotNull('kapor_sizes')->count(),
        ];
        $sizeSummary = $this->buildSizeSummary($studentBatch);

        return view('admin.students.show', compact('metrics', 'sizeSummary', 'studentBatch', 'students'));
    }

    public function addStudents(Request $request, StudentBatch $studentBatch)
    {
        if ($studentBatch->isArchived()) {
            return back()->with('error', 'Aktifkan kembali angkatan sebelum menambah siswa.');
        }

        $validated = $request->validate([
            'male_count' => ['required', 'integer', 'min:0', 'max:10000'],
            'female_count' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        $additionalCount = (int) $validated['male_count'] + (int) $validated['female_count'];
        $currentCount = $studentBatch->students()->count();

        if ($additionalCount < 1) {
            return back()->withErrors(['male_count' => 'Jumlah tambahan tidak boleh sama-sama nol.']);
        }
        if ($currentCount + $additionalCount > 10000) {
            return back()->withErrors(['male_count' => 'Total siswa dalam satu angkatan maksimal 10.000.']);
        }

        $created = $this->studentBatchService->addStudents(
            $studentBatch,
            (int) $validated['male_count'],
            (int) $validated['female_count'],
        );
        AuditLogger::log('Menambah siswa pada '.$studentBatch->code, 'Manajemen Siswa', $studentBatch, null, $validated, 'success', $created.' siswa tambahan dibuat.');

        return back()->with('success', $created.' siswa tambahan berhasil dibuat tanpa akun login.');
    }

    public function applySizeDistribution(Request $request, StudentBatch $studentBatch)
    {
        if ($studentBatch->isArchived()) {
            return back()->with('error', 'Aktifkan kembali angkatan sebelum mengatur ukuran.');
        }

        $validated = $request->validate([
            'size_key' => ['required', Rule::in(array_keys(StudentSizeDistributionService::SIZE_TYPES))],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'entries' => ['required', 'array', 'min:1', 'max:50'],
            'entries.*.size' => ['nullable', 'string', 'max:30'],
            'entries.*.count' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $result = $this->sizeDistributionService->apply(
            $studentBatch,
            $validated['size_key'],
            $validated['gender'] ?? null,
            $validated['entries'],
        );
        AuditLogger::log('Mengatur kuota '.$result['size_label'].' '.$studentBatch->code, 'Manajemen Siswa', $studentBatch, null, $result, 'success', $result['assigned'].' siswa menerima ukuran.');

        $message = $result['assigned'].' siswa berhasil diberi ukuran '.$result['size_label'].'.';
        if ($result['remaining'] > 0) {
            $message .= ' Tersisa '.$result['remaining'].' siswa yang belum dibagi untuk ukuran ini.';
        }

        return back()->with('success', $message);
    }

    public function export(StudentBatch $studentBatch)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        AuditLogger::log('Mengunduh data siswa '.$studentBatch->code, 'Manajemen Siswa', $studentBatch);

        return Excel::download(
            new StudentBatchExport($studentBatch),
            'Data_Siswa_'.$this->safeFilename($studentBatch->code).'.xlsx',
        );
    }

    public function import(Request $request, StudentBatch $studentBatch)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:30720']]);
        $payload = $this->importService->preview($studentBatch, $request->file('file'));
        $path = 'import-previews/students/'.$request->user()->id.'-'.$studentBatch->id.'-'.Str::uuid().'.json';
        Storage::disk('local')->put($path, json_encode($payload, JSON_THROW_ON_ERROR));
        $this->replacePreviewPath($request, $studentBatch, $path);

        return redirect()->route('admin.students.import-preview', $studentBatch);
    }

    public function importPreview(Request $request, StudentBatch $studentBatch)
    {
        $payload = $this->previewPayload($request, $studentBatch);
        if ($payload === null) {
            return redirect()->route('admin.students.show', $studentBatch)->with('error', 'Data pratinjau tidak ditemukan. Unggah kembali file Excel.');
        }

        return view('admin.students.import-preview', [
            'rows' => $payload['rows'],
            'stats' => $payload['stats'],
            'studentBatch' => $studentBatch,
        ]);
    }

    public function importConfirm(Request $request, StudentBatch $studentBatch)
    {
        $payload = $this->previewPayload($request, $studentBatch);
        if ($payload === null) {
            return redirect()->route('admin.students.show', $studentBatch)->with('error', 'Data pratinjau sudah tidak tersedia.');
        }
        if ((int) data_get($payload, 'stats.error', 0) > 0) {
            return redirect()->route('admin.students.import-preview', $studentBatch)->with('error', 'Perbaiki seluruh baris bermasalah sebelum menerapkan perubahan.');
        }

        $result = $this->importService->save($studentBatch, $payload['rows']);
        $this->clearPreview($request, $studentBatch);
        AuditLogger::log('Memperbarui data siswa '.$studentBatch->code, 'Manajemen Siswa', $studentBatch, null, $result, 'success', $result['updated'].' siswa diperbarui melalui Excel.');

        return redirect()->route('admin.students.show', $studentBatch)
            ->with('success', $result['updated'].' data siswa diperbarui. '.$result['unchanged'].' data tidak berubah.');
    }

    public function importCancel(Request $request, StudentBatch $studentBatch)
    {
        $this->clearPreview($request, $studentBatch);

        return redirect()->route('admin.students.show', $studentBatch)->with('info', 'Pratinjau unggahan dibatalkan.');
    }

    public function toggleArchive(StudentBatch $studentBatch)
    {
        $newStatus = $studentBatch->isArchived() ? StudentBatch::STATUS_ACTIVE : StudentBatch::STATUS_ARCHIVED;

        DB::transaction(function () use ($newStatus, $studentBatch): void {
            $studentBatch->update(['status' => $newStatus]);
            $studentBatch->students()->update([
                'is_active' => $newStatus === StudentBatch::STATUS_ACTIVE,
            ]);
        });

        AuditLogger::log(($newStatus === StudentBatch::STATUS_ARCHIVED ? 'Mengarsipkan' : 'Mengaktifkan').' angkatan '.$studentBatch->code, 'Manajemen Siswa', $studentBatch);

        return back()->with('success', $newStatus === StudentBatch::STATUS_ARCHIVED ? 'Angkatan berhasil diarsipkan.' : 'Angkatan berhasil diaktifkan kembali.');
    }

    private function previewPayload(Request $request, StudentBatch $batch): ?array
    {
        $path = $request->session()->get($this->previewSessionKey($batch));
        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $payload = json_decode(Storage::disk('local')->get($path), true);

        return is_array($payload) ? $payload : null;
    }

    private function replacePreviewPath(Request $request, StudentBatch $batch, string $path): void
    {
        $oldPath = $request->session()->get($this->previewSessionKey($batch));
        if (is_string($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }
        $request->session()->put($this->previewSessionKey($batch), $path);
    }

    private function clearPreview(Request $request, StudentBatch $batch): void
    {
        $key = $this->previewSessionKey($batch);
        $path = $request->session()->pull($key);
        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function previewSessionKey(StudentBatch $batch): string
    {
        return 'student_batch_import.'.$batch->id;
    }

    private function safeFilename(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '_', $value), '_');
    }

    private function buildSizeSummary(StudentBatch $batch): array
    {
        $students = $batch->students()->get(['gender', 'kapor_sizes']);

        return collect(StudentSizeDistributionService::SIZE_TYPES)
            ->map(function (string $label, string $key) use ($students): array {
                $values = $students
                    ->map(fn (Personnel $student) => data_get($student->kapor_sizes, $key))
                    ->filter(fn ($value) => filled($value));

                return [
                    'key' => $key,
                    'label' => $label,
                    'filled' => $values->count(),
                    'distribution' => $values->countBy()->sortDesc()->take(4)->all(),
                ];
            })
            ->values()
            ->all();
    }
}
