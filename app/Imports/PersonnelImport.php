<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PersonnelImport implements SkipsUnknownSheets, ToCollection, WithMultipleSheets, WithStartRow
{
    protected $successCount = 0;

    protected $errorCount = 0;

    protected $errors = [];

    protected $satkerId;

    public function sheets(): array
    {
        // Return kosong = fallback ke default (semua sheet diproses oleh class ini sendiri)
        // karena kita mengimplementasikan ToCollection di class ini juga.
        // Tapi cara paling aman agar import dipanggil untuk tiap sheet adalah:
        return [
            0 => $this,
            1 => $this,
            2 => $this,
        ];
    }

    public function onUnknownSheet($sheetName)
    {
        // Abaikan sheet lain yang tidak ada di index 0, 1, 2
    }

    /**
     * Mapping koreksi typo pangkat yang umum.
     * Key = variasi typo (uppercase), Value = nama standar di DB.
     */
    public static function getPangkatCorrectionMap(): array
    {
        return [
            // ── PATI ──
            'IRJEND' => 'IRJEN',
            'IRJEND POL' => 'IRJEN',
            'IRJEN POL' => 'IRJEN',
            'BRIGJEND' => 'BRIGJEN',
            'BRIGJEN POL' => 'BRIGJEN',
            'BRIGJEND POL' => 'BRIGJEN',
            'BRIG' => 'BRIGJEN',

            // ── PAMEN ──
            'KOMBESPOL' => 'KOMBES POL',
            'KOMBES.' => 'KOMBES POL',
            'KOMBES' => 'KOMBES POL',
            'AKBP.' => 'AKBP',
            'KOMPOL.' => 'KOMPOL',

            // ── PAMA ──
            'AKP.' => 'AKP',
            'IPTU.' => 'IPTU',
            'IPDA.' => 'IPDA',

            // ── BINTARA ──
            'AIPTU.' => 'AIPTU',
            'AIPDA.' => 'AIPDA',
            'BRIPKA.' => 'BRIPKA',
            'BRIGADIR POL' => 'BRIGADIR',
            'BRIGADIRPOL' => 'BRIGADIR',
            'BRIGPOL' => 'BRIGADIR',
            'BRIIGPOL' => 'BRIGADIR',
            'BRIGOL' => 'BRIGADIR',
            'BRIG POL' => 'BRIGADIR',
            'BRIPTU.' => 'BRIPTU',
            'BRIPDA.' => 'BRIPDA',
            'ABRIPTU.' => 'ABRIPTU',
            'ABRIPDA.' => 'ABRIPDA',
            'BHARAKA.' => 'BHARAKA',
            'BHARATU.' => 'BHARATU',
            'BHARADA.' => 'BHARADA',

            // ── Typo 1 huruf POLRI ──
            'IRJED' => 'IRJEN',
            'IRGJEN' => 'IRJEN',
            'BRIGGEN' => 'BRIGJEN',
            'KOMBESS' => 'KOMBES POL',
            'AKBPP' => 'AKBP',
            'KOMPOLL' => 'KOMPOL',
            'AKB' => 'AKBP',
            'AIPTO' => 'AIPTU',
            'AIPTU ' => 'AIPTU',
            'AIPDA ' => 'AIPDA',
            'BRIPKKA' => 'BRIPKA',
            'BRIGADI' => 'BRIGADIR',
            'BRIAGDIR' => 'BRIGADIR',
            'BRIGADR' => 'BRIGADIR',
            'BRIPDA ' => 'BRIPDA',
            'BHARAKA ' => 'BHARAKA',
            'BHARATU ' => 'BHARATU',
            'BHARADA ' => 'BHARADA',

            // ── PNS — SINGKATAN UMUM (sering dipakai di file Excel satker) ──
            // Pembina
            'PEMB' => 'Pembina',
            'PEMB UTM' => 'Pembina Utama',
            'PEMB UTAMA' => 'Pembina Utama',
            'PEMBINA UTM' => 'Pembina Utama',
            'PEMBINA UTAMA MADY' => 'Pembina Utama Madya',
            'PEMBINA UTAMA MUUDA' => 'Pembina Utama Muda',
            'PEMBIINA' => 'Pembina',
            'PEMBIINA UTAMA' => 'Pembina Utama',
            'PEMBTU' => 'Pembina Tingkat I',
            'PEMB TU' => 'Pembina Tingkat I',
            'PEMBINA TK.I' => 'Pembina Tingkat I',
            'PEMBINA TK I' => 'Pembina Tingkat I',
            'PEMBINA TKT I' => 'Pembina Tingkat I',
            'PEMBINA TINGKAT 1' => 'Pembina Tingkat I',
            'PEMBINA TK 1' => 'Pembina Tingkat I',
            'PEMBINA TK. 1' => 'Pembina Tingkat I',

            // Penata Tingkat I
            'PENTU' => 'Penata Tingkat I',
            'PEN TU' => 'Penata Tingkat I',
            'PNTA TK I' => 'Penata Tingkat I',
            'PENATA TK.I' => 'Penata Tingkat I',
            'PENATA TK I' => 'Penata Tingkat I',
            'PENATA TKT I' => 'Penata Tingkat I',
            'PENATA TINGKAT 1' => 'Penata Tingkat I',
            'PENATA TK 1' => 'Penata Tingkat I',
            'PENATA TK1' => 'Penata Tingkat I',
            'PENATA TK. 1' => 'Penata Tingkat I',
            'PENATA TK II' => 'Penata Tingkat I', // Dianggap typo
            'PENATA I' => 'Penata Tingkat I',
            'PENATA 1' => 'Penata Tingkat I',

            // Penata Muda Tingkat I (PENDATU / PENDA I)
            'PENDATU' => 'Penata Muda Tingkat I',
            'PENDA I' => 'Penata Muda Tingkat I',
            'PENDA 1' => 'Penata Muda Tingkat I',
            'PEN DATU' => 'Penata Muda Tingkat I',
            'PNTA MDU TK I' => 'Penata Muda Tingkat I',
            'PENATA MD TK I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK.I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TKT I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TINGKAT 1' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK 1' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK.1' => 'Penata Muda Tingkat I',
            'PENATA MDU TK I' => 'Penata Muda Tingkat I',

            // Penata Muda (PENDA)
            'PENDA' => 'Penata Muda',
            'PEN DA' => 'Penata Muda',
            'PENATA MD' => 'Penata Muda',
            'PENATA MDU' => 'Penata Muda',
            'PNTA MDU' => 'Penata Muda',
            'PENATA MDA' => 'Penata Muda',

            // Pengatur Tingkat I
            'PENGTU' => 'Pengatur Tingkat I',
            'PENG TU' => 'Pengatur Tingkat I',
            'PENGATUR TK.I' => 'Pengatur Tingkat I',
            'PENGATUR TK I' => 'Pengatur Tingkat I',
            'PENGATUR TK. I/II.D' => 'Pengatur Tingkat I',
            'PENGATUR TK I/II.D' => 'Pengatur Tingkat I',
            'PENGATUR TK I/II D' => 'Pengatur Tingkat I',
            'PENGATUR TK.I/II.D' => 'Pengatur Tingkat I',
            'PENGATUR TK L' => 'Pengatur Tingkat I',
            'PENGATUR TKT I' => 'Pengatur Tingkat I',
            'PENGATUR TINGKAT 1' => 'Pengatur Tingkat I',
            'PENGATUR TK 1' => 'Pengatur Tingkat I',
            'PENGATUR TK1' => 'Pengatur Tingkat I',
            'PENGATUR TK. 1' => 'Pengatur Tingkat I',
            'PENGATUR I' => 'Pengatur Tingkat I',
            'PENGATUR 1' => 'Pengatur Tingkat I',

            // Pengatur Muda Tingkat I (PENGDATU / PENGDA I)
            'PENGDATU' => 'Pengatur Muda Tingkat I',
            'PENGDA I' => 'Pengatur Muda Tingkat I',
            'PENGDA 1' => 'Pengatur Muda Tingkat I',
            'PENG DATU' => 'Pengatur Muda Tingkat I',
            'PENGATUR MD TK I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK.I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TKT I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TINGKAT 1' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK 1' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK.1' => 'Pengatur Muda Tingkat I',
            'PENGATUR MDU TK I' => 'Pengatur Muda Tingkat I',

            // Pengatur Muda (PENGDA)
            'PENGDA' => 'Pengatur Muda',
            'PENG DA' => 'Pengatur Muda',
            'PENGATUR MD' => 'Pengatur Muda',
            'PENGATUR MDU' => 'Pengatur Muda',
            'PENGATUR MDA' => 'Pengatur Muda',

            // Juru Tingkat I
            'JURTU' => 'Juru Tingkat I',
            'JUR TU' => 'Juru Tingkat I',
            'JURU TK.I' => 'Juru Tingkat I',
            'JURU TK I' => 'Juru Tingkat I',
            'JURU TKT I' => 'Juru Tingkat I',
            'JURU TINGKAT 1' => 'Juru Tingkat I',
            'JURU TK 1' => 'Juru Tingkat I',
            'JURU TK. 1' => 'Juru Tingkat I',

            // Juru Muda Tingkat I (JURDATU / JURDA I)
            'JURDATU' => 'Juru Muda Tingkat I',
            'JURDA I' => 'Juru Muda Tingkat I',
            'JURDA 1' => 'Juru Muda Tingkat I',
            'JUR DATU' => 'Juru Muda Tingkat I',
            'JURU MD TK I' => 'Juru Muda Tingkat I',
            'JURU MUDA TK.I' => 'Juru Muda Tingkat I',
            'JURU MUDA TK I' => 'Juru Muda Tingkat I',
            'JURU MUDA TKT I' => 'Juru Muda Tingkat I',
            'JURU MUDA TINGKAT 1' => 'Juru Muda Tingkat I',
            'JURU MUDA TK 1' => 'Juru Muda Tingkat I',

            // Juru Muda (JURDA)
            'JURDA' => 'Juru Muda',
            'JUR DA' => 'Juru Muda',
            'JURU MD' => 'Juru Muda',
            'JURU MDU' => 'Juru Muda',

            // ── PPPK — semua varian PPPK/P3K dikoreksi ke 'PPPK' ──
            'PPPK GOLONGAN I' => 'PPPK',
            'PPPK GOLONGAN II' => 'PPPK',
            'PPPK GOLONGAN III' => 'PPPK',
            'PPPK GOLONGAN IV' => 'PPPK',
            'PPPK GOLONGAN V' => 'PPPK',
            'PPPK GOLONGAN VI' => 'PPPK',
            'PPPK GOLONGAN VII' => 'PPPK',
            'PPPK GOLONGAN VIII' => 'PPPK',
            'PPPK GOLONGAN IX' => 'PPPK',
            'PPPK GOLONGAN X' => 'PPPK',
            'PPPK GOL II' => 'PPPK',
            'PPPK GOL. II' => 'PPPK',
            'PPPK GOL III' => 'PPPK',
            'PPPK GOL. III' => 'PPPK',
            'P3K' => 'PPPK',
            'P3K GOLONGAN 2' => 'PPPK',
            'P3K GOL II' => 'PPPK',
            'P3K GOL. II' => 'PPPK',
        ];
    }

    /**
     * Mencari pangkat yang cocok dari daftar pangkat dengan logika:
     * 1. Exact match (uppercase)
     * 2. Cek correction map (typo map)
     * 3. Similar text match (jika Levenshtein distance ≤ 2)
     *
     * Return: ['rank' => Rank|null, 'corrected' => bool, 'original' => string, 'corrected_to' => string|null]
     */
    public static function findRankWithCorrection(string $rankName, Collection $ranks, string $golongan = ''): array
    {
        $upperInput = strtoupper(trim($rankName));

        // Bersihkan embel-embel golongan PNS jika menempel di nama pangkat (misal: "PENATA/IIIC", "PENGATUR TK.I/II.D")
        $upperInput = preg_replace('/\s*\/?\s*(IV|III|II|I)\.?\s*[A-E]\s*$/i', '', $upperInput);
        $upperInput = trim($upperInput);

        // --- KHUSUS PPPK ---
        // Jika pangkat yang dimasukkan adalah "PPPK" atau "P3K", langsung set ke 'PPPK'
        $cleanInput = trim($upperInput, '.');
        if ($cleanInput === 'PPPK' || $cleanInput === 'P3K') {
            $upperInput = 'PPPK';
        }

        $correctionMap = self::getPangkatCorrectionMap();

        // 1. Exact match
        $rank = $ranks->get($upperInput);
        if ($rank) {
            return ['rank' => $rank, 'corrected' => false, 'original' => $rankName, 'corrected_to' => null];
        }

        // 2. Cek di correction map
        if (isset($correctionMap[$upperInput])) {
            $correctedName = $correctionMap[$upperInput];
            $rank = $ranks->get(strtoupper($correctedName));
            // Coba case-insensitive juga untuk PNS
            if (! $rank) {
                foreach ($ranks as $key => $r) {
                    if (strtolower($key) === strtolower($correctedName)) {
                        $rank = $r;
                        break;
                    }
                }
            }
            if ($rank) {
                return ['rank' => $rank, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $rank->name];
            }
        }

        // 3. Levenshtein / similar_text match terhadap semua pangkat
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;
        foreach ($ranks as $key => $r) {
            $dist = levenshtein($upperInput, strtoupper($key));
            if ($dist < $bestDistance) {
                $bestDistance = $dist;
                $bestMatch = $r;
            }
        }

        if ($bestDistance <= 2 && $bestMatch) {
            return ['rank' => $bestMatch, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $bestMatch->name];
        }

        return ['rank' => null, 'corrected' => false, 'original' => $rankName, 'corrected_to' => null];
    }

    public function __construct($satkerId = null)
    {
        $this->satkerId = $satkerId;
    }

    /**
     * Start reading data from Row 11 (after the 10-row header in the template)
     */
    public function startRow(): int
    {
        return 11;
    }

    /**
     * Parse rows menjadi array preview data (TANPA menyimpan ke DB).
     * Digunakan untuk menampilkan preview sebelum konfirmasi import.
     */
    public function generatePreview(Collection $rows): array
    {
        $ranks = Rank::all()->keyBy(fn ($r) => strtoupper($r->name));
        $preview = [];

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            // Bersihkan tanda bintang (*) dan normalisasi enter/spasi ganda (karena sering ada \n di dalam cell Excel)
            $fullNameRtn = str_replace('*', '', $row[1] ?? '');
            $fullName = trim(preg_replace('/\s+/', ' ', $fullNameRtn));

            $rankInputRtn = str_replace('*', '', $row[2] ?? '');
            $rankInput = trim(preg_replace('/\s+/', ' ', $rankInputRtn));

            $golongan = trim($row[3] ?? '');
            $nrpRaw = $row[4] ?? '';
            // Excel menyimpan NIP panjang (18 digit) sebagai float → dibaca sebagai scientific notation
            // Contoh: 196905181990031002 → 1.96905181990031E+17
            // Konversi ke string integer penuh agar NIP tampil benar
            if (is_numeric($nrpRaw) && (is_float($nrpRaw) || stripos((string)$nrpRaw, 'E') !== false)) {
                $nrp = number_format((float)$nrpRaw, 0, '', '');
            } else {
                $nrp = trim((string)$nrpRaw);
            }
            $jabatan = trim($row[5] ?? '');
            $bagian = trim($row[6] ?? '');
            $genderRaw = strtoupper(trim($row[7] ?? ''));
            $keterangan = trim($row[17] ?? '');

            // Ambil nomor urut dari kolom pertama (NO)
            $no = trim($row[0] ?? '');

            // Jika Nama kosong, dipastikan baris tidak valid/kosong/rekap
            if (empty($fullName)) {
                continue;
            }

            // Jika pangkat kosong, cek apakah baris punya nomor urut (kolom NO)
            // Personel asli selalu punya nomor urut (1, 2, 3, ...)
            // Baris header/catatan (TUTUP KEPALA, BAJU PRIA, dll) tidak punya nomor urut
            if (empty($rankInput) && !is_numeric($no)) {
                continue;
            }

            // --- FILTER BARIS KOTOR / REKAPITULASI ---
            $nameLower = strtolower($fullName);

            // 1. Abaikan baris jika Nama diawali '=' (rumus excel) atau adalah string "jumlah"/"total"
            if (str_starts_with($fullName, '=')) {
                continue;
            }
            // Cegah false positive, misal ada orang bernama "Total", hanya tolak bila namanya persis "JUMLAH" atau "TOTAL"
            if ($nameLower === 'jumlah' || $nameLower === 'total') {
                continue;
            }

            // 2. Abaikan baris jika Nama SANGAT PENDEK / berupa angka (seperti baris rekap -> Nama "14", "15", "K")
            // Nama personel asli umumnya >= 2 huruf dan tidak cuma terdiri dari angka.
            if (strlen($fullName) < 2 || is_numeric(str_replace([' ', '.', ','], '', $fullName))) {
                continue;
            }

            // 3. Pengecekan NRP/NIP
            if (empty($nrp)) {
                // Jika NRP kosong, biarkan kosong — akan di-generate saat save jika diperlukan
                // Ini agar di preview tampil '—' bukan kode acak
                $nrp = '';
            } else {
                $nrpLower = strtolower($nrp);
                // Abaikan jika NRP/NIP berisi kata rekapan atau rumus
                if (str_starts_with($nrp, '=')) {
                    continue;
                }
                if ($nrpLower === 'jumlah' || $nrpLower === 'total') {
                    continue;
                }

                // Abaikan jika NRP berupa angka float (misal "15.5") atau terlampau pendek (< 5 karakter)
                // Filter is_numeric($nrp) dihapus untuk berjaga-jaga NIP mengandung karakter aneh tapi valid
                if (strlen($nrp) < 4) {
                    continue;
                }
            }

            // Gender: P di Excel → L (Laki-laki), W di Excel → P (Perempuan)
            $gender = ($genderRaw === 'W') ? 'P' : 'L';

            // Koreksi pangkat (sekarang menyertakan golongan untuk PPPK)
            $rankResult = self::findRankWithCorrection($rankInput, $ranks, $golongan);

            // Ukuran kapor (kolom I-Q, index 8-16)
            $sizes = [
                'topi' => trim($row[8] ?? ''),
                'kemeja' => trim($row[9] ?? ''),
                'celana' => trim($row[10] ?? ''),
                'olahraga' => trim($row[11] ?? ''),
                'sepatu_dinas' => trim($row[12] ?? ''),
                'sepatu_olahraga' => trim($row[13] ?? ''),
                'jaket' => trim($row[14] ?? ''),
                'sabuk' => trim($row[15] ?? ''),
                'jilbab' => trim($row[16] ?? ''),
            ];

            // Pria tidak butuh jilbab — hapus dari data ukuran
            if ($gender === 'L') {
                unset($sizes['jilbab']);
            }

            $status = 'ok';
            $incompleteFields = [];

            // Cek kelengkapan data — kumpulkan semua field yang kosong
            if (! $rankResult['rank'] && ! empty($rankInput)) {
                $status = 'error'; // pangkat diisi tapi tidak dikenali → perlu pilih manual
            } elseif (! $rankResult['rank'] && empty($rankInput)) {
                $incompleteFields[] = 'Pangkat';
            }
            if (empty($nrp)) {
                $incompleteFields[] = 'NRP/NIP';
            }

            // Jika pangkat dikoreksi otomatis (misal PPPK, KOMBES POL, dll)
            if ($rankResult['corrected'] && $status !== 'error') {
                $status = 'corrected';
            }

            // Jika ada field yang belum lengkap, masukkan ke Auto Koreksi agar admin bisa lihat
            if (! empty($incompleteFields) && $status !== 'error') {
                $status = 'corrected';
                // Tambahkan info field yang kosong ke corrected_to
                $missingLabel = '— (Belum Lengkap: ' . implode(', ', $incompleteFields) . ')';
                if ($rankResult['corrected_to']) {
                    // Sudah ada koreksi pangkat, tambahkan info missing
                    $rankResult['corrected_to'] .= ' | ' . implode(', ', $incompleteFields) . ' kosong';
                } else {
                    $rankResult['corrected'] = true;
                    $rankResult['corrected_to'] = $missingLabel;
                }
            }

            $preview[] = [
                'row_num' => $rowIndex + 11, // baris di Excel
                'full_name' => $fullName,
                'rank_input' => $rankInput, // input asli dari Excel
                'rank_corrected' => $rankResult['corrected_to'], // null jika tidak dikoreksi
                'rank_id' => $rankResult['rank']?->id,
                'rank_name' => $rankResult['rank']?->name,
                'golongan' => $golongan,
                'nrp' => $nrp,
                'jabatan' => $jabatan,
                'bagian' => $bagian,
                'gender' => $gender,
                'gender_raw' => $genderRaw,
                'keterangan' => $keterangan,
                'sizes' => $sizes,
                'status' => $status, // 'ok' | 'corrected' | 'error'
                'incomplete_fields' => $incompleteFields, // field yang kosong
            ];
        }

        return $preview;
    }

    /**
     * Simpan data dari preview — aman (Eloquent) + cepat (satu transaksi, pre-load, bcrypt cost rendah).
     */
    public function saveFromPreviewData(array $rows, int $satkerId): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $satker = Satker::findOrFail($satkerId);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $sizeMapping = ['topi', 'kemeja', 'celana', 'olahraga', 'sepatu_dinas', 'sepatu_olahraga', 'jaket', 'sabuk', 'jilbab'];

        // ── Pre-load sekali agar tidak ada N+1 query ─────────────────────────
        $ranksById = Rank::all()->keyBy('id');
        $allNrp = collect($rows)->pluck('nrp')->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
        $existingPersonnel = Personnel::whereIn('nrp', $allNrp)->get()->keyBy('nrp');
        $existingUsers = User::whereIn('nrp_nip', $allNrp)->get()->keyBy('nrp_nip');

        // ── Satu transaksi besar untuk semua insert/update ───────────────────
        DB::transaction(function () use (
            $rows, $satker, $ranksById, $existingPersonnel, $existingUsers,
            $sizeMapping, &$successCount, &$errorCount, &$errors
        ) {
            foreach ($rows as $idx => $data) {
                $nrp = trim($data['nrp'] ?? '');
                $fullName = trim($data['full_name'] ?? '');
                $rankId = (int) ($data['rank_id'] ?? 0);
                $gender = $data['gender'] ?? 'L';
                $jabatan = trim($data['jabatan'] ?? '');
                $bagian = trim($data['bagian'] ?? '');
                $golongan = trim($data['golongan'] ?? '');
                $keterangan = trim($data['keterangan'] ?? '');
                $sizes = $data['sizes'] ?? [];

                if (empty($fullName)) {
                    $errorCount++;
                    $errors[] = "Baris {$idx}: Nama kosong, dilewati.";

                    continue;
                }

                $rank = $rankId ? $ranksById->get($rankId) : null;
                if ($rankId && ! $rank) {
                    $errorCount++;
                    $errors[] = "Baris {$idx}: Pangkat ID={$rankId} tidak ditemukan (NRP: {$nrp}).";

                    continue;
                }

                try {
                    // Jika NRP kosong, generate ID sementara untuk akun user (login)
                    // tapi simpan personnels.nrp sebagai NULL agar tampil "—" di UI
                    $effectiveNrp = $nrp;
                    $isEmptyNrp = empty($nrp);
                    if ($isEmptyNrp) {
                        $effectiveNrp = 'TEMP-' . strtoupper(substr(md5($fullName . $idx . time()), 0, 8));
                    }

                    $personnel = $isEmptyNrp ? null : $existingPersonnel->get($effectiveNrp);

                    if (! $personnel) {
                        // ── User baru: bcrypt cost=4 (10× lebih cepat, password bisa diubah nanti) ──
                        $user = $existingUsers->get($effectiveNrp);
                        if (! $user) {
                            $user = User::create([
                                'name' => $fullName,
                                'nrp_nip' => $effectiveNrp,
                                'password' => password_hash($effectiveNrp, PASSWORD_BCRYPT, ['cost' => 4]),
                                'satker_id' => $satker->id,
                                'is_active' => true,
                            ]);
                            $user->assignRole('personil');
                            $existingUsers->put($effectiveNrp, $user);
                        }

                        // Tentukan personnel_type berdasarkan rank atau default
                        $personnelType = 'Polri';
                        if ($rank && $rank->category === 'PNS') {
                            $personnelType = 'PNS';
                        } elseif (! $rank && ! empty($golongan)) {
                            // Jika tidak ada pangkat tapi ada golongan, kemungkinan PNS
                            $personnelType = 'PNS';
                        }

                        $personnel = Personnel::create([
                            'user_id' => $user->id,
                            'nrp' => $isEmptyNrp ? null : $effectiveNrp,
                            'full_name' => $fullName,
                            'rank_id' => $rank ? $rank->id : null,
                            'satker_id' => $satker->id,
                            'jabatan' => $jabatan,
                            'bagian' => $bagian,
                            'personnel_type' => $personnelType,
                            'gender' => $gender,
                            'golongan' => $golongan,
                            'keterangan' => $keterangan,
                            'is_active' => true,
                        ]);
                        $existingPersonnel->put($effectiveNrp, $personnel);
                    } else {
                        // ── Update personel yang sudah ada ──
                        $updateData = [
                            'full_name' => $fullName,
                            'satker_id' => $satker->id,
                            'jabatan' => $jabatan,
                            'bagian' => $bagian,
                            'golongan' => $golongan,
                            'keterangan' => $keterangan,
                            'gender' => $gender,
                        ];
                        // Hanya update rank_id jika ada
                        if ($rank) {
                            $updateData['rank_id'] = $rank->id;
                        }
                        $personnel->update($updateData);
                    }

                    // Simpan ukuran kapor
                    $kaporSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];
                    foreach ($sizeMapping as $key) {
                        $val = trim($sizes[$key] ?? '');
                        if (! empty($val) && $val !== '-' && $val !== '0') {
                            $kaporSizes[$key] = $val;
                        }
                    }

                    // Pria tidak butuh jilbab — hapus dari kapor_sizes
                    if ($gender === 'L') {
                        unset($kaporSizes['jilbab']);
                    }

                    $personnel->kapor_sizes = $kaporSizes;
                    $personnel->save();

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Baris {$idx} (NRP: {$nrp}): ".$e->getMessage();
                }
            }
        });

        self::recalculateSatkerCount($satkerId);

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * Helper: build kapor_sizes JSON string dari data existing + data baru.
     */
    private function buildKaporSizes(array $existing, array $newSizes, array $mapping): string
    {
        $result = $existing;
        foreach ($mapping as $key) {
            $val = trim($newSizes[$key] ?? '');
            if (! empty($val) && $val !== '-' && $val !== '0') {
                $result[$key] = $val;
            }
        }

        return json_encode($result);
    }

    /**
     * Hitung ulang jumlah Polri dan PNS pada satker setelah import.
     */
    public static function recalculateSatkerCount(int $satkerId): void
    {
        $polriCount = Personnel::where('satker_id', $satkerId)
            ->where('personnel_type', 'Polri')
            ->where('is_active', true)
            ->count();

        $pnsCount = Personnel::where('satker_id', $satkerId)
            ->where('personnel_type', 'PNS')
            ->where('is_active', true)
            ->count();

        Satker::where('id', $satkerId)->update([
            'polri_count' => $polriCount,
            'pns_count' => $pnsCount,
        ]);
    }

    /**
     * ToCollection — dipanggil oleh Maatwebsite Excel saat import langsung.
     * (Dipertahankan untuk kompatibilitas, tapi alur utama kini via preview)
     */
    public function collection(Collection $rows)
    {
        // Alur lama dipertahankan sebagai fallback
        $ranks = Rank::all()->keyBy(fn ($r) => strtoupper($r->name));
        $satker = Satker::find($this->satkerId) ?? Satker::first();
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        $sizeMapping = [
            8 => 'topi',
            9 => 'kemeja',
            10 => 'celana',
            11 => 'olahraga',
            12 => 'sepatu_dinas',
            13 => 'sepatu_olahraga',
            14 => 'jaket',
            15 => 'sabuk',
            16 => 'jilbab',
        ];

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            $fullName = trim($row[1] ?? '');
            $rankInput = trim($row[2] ?? '');
            $golongan = trim($row[3] ?? '');
            $nrp = trim($row[4] ?? '');
            $jabatan = trim($row[5] ?? '');
            $bagian = trim($row[6] ?? '');
            $genderRaw = strtoupper(trim($row[7] ?? ''));
            $keterangan = trim($row[17] ?? '');

            if (empty($nrp) || empty($fullName)) {
                continue;
            }

            // Gender: P di Excel → L (Laki-laki), W di Excel → P (Perempuan)
            $gender = ($genderRaw === 'W') ? 'P' : 'L';

            $rankResult = self::findRankWithCorrection($rankInput, $ranks);
            $rank = $rankResult['rank'];

            if (! $rank) {
                $this->errorCount++;
                $this->errors[] = 'Baris '.($rowIndex + 11)." (NRP: {$nrp}): Pangkat '{$rankInput}' tidak ditemukan.";

                continue;
            }

            try {
                DB::transaction(function () use ($row, $nrp, $fullName, $rank, $satker, $jabatan, $bagian, $gender, $golongan, $keterangan, $sizeMapping) {
                    $personnel = Personnel::where('nrp', $nrp)->first();

                    if (! $personnel) {
                        $user = User::create([
                            'name' => $fullName,
                            'nrp_nip' => $nrp,
                            'password' => Hash::make($nrp),
                            'satker_id' => $satker->id,
                            'is_active' => true,
                        ]);
                        $user->assignRole('personil');

                        $personnel = Personnel::create([
                            'user_id' => $user->id,
                            'nrp' => $nrp,
                            'full_name' => $fullName,
                            'rank_id' => $rank->id,
                            'satker_id' => $satker->id,
                            'jabatan' => $jabatan,
                            'bagian' => $bagian,
                            'personnel_type' => $rank->category === 'PNS' ? 'PNS' : 'Polri',
                            'gender' => $gender,
                            'golongan' => $golongan,
                            'keterangan' => $keterangan,
                            'is_active' => true,
                        ]);
                    } else {
                        $personnel->update([
                            'full_name' => $fullName,
                            'rank_id' => $rank->id,
                            'satker_id' => $satker->id,
                            'jabatan' => $jabatan,
                            'bagian' => $bagian,
                            'golongan' => $golongan,
                            'keterangan' => $keterangan,
                            'gender' => $gender,
                        ]);
                    }

                    $kaporSizes = $personnel->kapor_sizes ?? [];
                    foreach ($sizeMapping as $colIndex => $key) {
                        $sizeVal = trim($row[$colIndex] ?? '');
                        if (! empty($sizeVal) && $sizeVal !== '-' && $sizeVal !== '0') {
                            $kaporSizes[$key] = $sizeVal;
                        }
                    }

                    // Pria tidak butuh jilbab — hapus dari kapor_sizes
                    if ($gender === 'L') {
                        unset($kaporSizes['jilbab']);
                    }

                    $personnel->kapor_sizes = $kaporSizes;
                    $personnel->save();
                });
                $this->successCount++;
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->errors[] = 'Baris '.($rowIndex + 11)." (NRP: {$nrp}): ".$e->getMessage();
            }
        }

        if ($this->satkerId) {
            self::recalculateSatkerCount($this->satkerId);
        }
    }

    public function getResults()
    {
        return [
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'errors' => $this->errors,
        ];
    }
}
