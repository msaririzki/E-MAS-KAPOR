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
            'BINTARA' => 'BRIGADIR', // kategori BINTARA → default ke BRIGADIR
            'BA BRIMOB' => 'BRIGADIR', // Bintara Brimob → default ke BRIGADIR
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
            // Pembina Utama
            'PEMB UTM' => 'Pembina Utama',
            'PEMB UTAMA' => 'Pembina Utama',
            'PEMBINA UTM' => 'Pembina Utama',
            'PEMBIINA UTAMA' => 'Pembina Utama',
            // Pembina Utama Madya
            'PEMBINA UTAMA MADY' => 'Pembina Utama Madya',
            'PEMBINA UTM MADYA' => 'Pembina Utama Madya',
            'PEMB UTM MADYA' => 'Pembina Utama Madya',
            'PEMBINA UTM MDY' => 'Pembina Utama Madya',
            // Pembina Utama Muda
            'PEMBINA UTAMA MUUDA' => 'Pembina Utama Muda',
            'PEMBINA UTM MUDA' => 'Pembina Utama Muda',
            'PEMB UTM MUDA' => 'Pembina Utama Muda',
            'PEMBINA UTM MDU' => 'Pembina Utama Muda',
            // Pembina Tingkat I
            'PEMBTU' => 'Pembina Tingkat I',
            'PEMB TU' => 'Pembina Tingkat I',
            'PEMBINA TK.I' => 'Pembina Tingkat I',
            'PEMBINA TK.1' => 'Pembina Tingkat I',
            'PEMBINA TK1' => 'Pembina Tingkat I',
            'PEMBINA TK. I' => 'Pembina Tingkat I',
            'PEMBINA TK I' => 'Pembina Tingkat I',
            'PEMBINA TKT I' => 'Pembina Tingkat I',
            'PEMBINA TINGKAT 1' => 'Pembina Tingkat I',
            'PEMBINA TK 1' => 'Pembina Tingkat I',
            'PEMBINA TK. 1' => 'Pembina Tingkat I',
            'PEMBINA I' => 'Pembina Tingkat I',
            'PEMBINA 1' => 'Pembina Tingkat I',
            // Pembina (ambigu — bisa jadi Pembina / Pembina Tingkat I, golongan akan membantu)
            'PEMB' => 'Pembina',
            'PEMBIINA' => 'Pembina',

            // Penata Tingkat I
            'PENTU' => 'Penata Tingkat I',
            'PEN TU' => 'Penata Tingkat I',
            'PNTA TK I' => 'Penata Tingkat I',
            'PENATA TK.I' => 'Penata Tingkat I',
            'PENATA TK.1' => 'Penata Tingkat I',
            'PENATA TK. I' => 'Penata Tingkat I',
            'PENATA TK I' => 'Penata Tingkat I',
            'PENATA TKT I' => 'Penata Tingkat I',
            'PENATA TINGKAT 1' => 'Penata Tingkat I',
            'PENATA TK 1' => 'Penata Tingkat I',
            'PENATA TK1' => 'Penata Tingkat I',
            'PENATA TK. 1' => 'Penata Tingkat I',
            'PENATA TK II' => 'Penata Tingkat I', // Dianggap typo
            'PENATA I' => 'Penata Tingkat I',
            'PENATA 1' => 'Penata Tingkat I',
            // Penata (ambigu)
            'PNTA' => 'Penata',
            'PENAT' => 'Penata',
            'PENAAT' => 'Penata',

            // Penata Muda Tingkat I (PENDATU / PENDA I)
            'PENDATU' => 'Penata Muda Tingkat I',
            'PENDA I' => 'Penata Muda Tingkat I',
            'PENDA 1' => 'Penata Muda Tingkat I',
            'PENDA TK I' => 'Penata Muda Tingkat I',
            'PENDA TK. I' => 'Penata Muda Tingkat I',
            'PENDA TK 1' => 'Penata Muda Tingkat I',
            'PENDA TK.I' => 'Penata Muda Tingkat I',
            'PENDA TK.1' => 'Penata Muda Tingkat I',
            'PENDA. TK. I' => 'Penata Muda Tingkat I',
            'PENDA. TK.I' => 'Penata Muda Tingkat I',
            'PENDA. TK I' => 'Penata Muda Tingkat I',
            'PENDA. TK.1' => 'Penata Muda Tingkat I',
            'PENDA. TK 1' => 'Penata Muda Tingkat I',
            'PENDA. I' => 'Penata Muda Tingkat I',
            'PENDA. 1' => 'Penata Muda Tingkat I',
            'PEN DATU' => 'Penata Muda Tingkat I',
            'PNTA MDU TK I' => 'Penata Muda Tingkat I',
            'PENATA MD TK I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK.I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK.1' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK. I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TKT I' => 'Penata Muda Tingkat I',
            'PENATA MUDA TINGKAT 1' => 'Penata Muda Tingkat I',
            'PENATA MUDA TK 1' => 'Penata Muda Tingkat I',
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
            'PENGATUR TK.1' => 'Pengatur Tingkat I',
            'PENGATUR TK. I' => 'Pengatur Tingkat I',
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
            // Pengatur (ambigu)
            'PENGATU' => 'Pengatur',
            'PENGATR' => 'Pengatur',
            'PENGAUR' => 'Pengatur',
            'PENGTR' => 'Pengatur',

            // Pengatur Muda Tingkat I (PENGDATU / PENGDA I)
            'PENGDATU' => 'Pengatur Muda Tingkat I',
            'PENGDA I' => 'Pengatur Muda Tingkat I',
            'PENGDA 1' => 'Pengatur Muda Tingkat I',
            'PENGDA TK.I' => 'Pengatur Muda Tingkat I',
            'PENGDA TK.1' => 'Pengatur Muda Tingkat I',
            'PENG DATU' => 'Pengatur Muda Tingkat I',
            'PENGATUR MD TK I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK.I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK.1' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK. I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TKT I' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TINGKAT 1' => 'Pengatur Muda Tingkat I',
            'PENGATUR MUDA TK 1' => 'Pengatur Muda Tingkat I',
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
            'JURU TK.1' => 'Juru Tingkat I',
            'JURU TK. I' => 'Juru Tingkat I',
            'JURU TK I' => 'Juru Tingkat I',
            'JURU TKT I' => 'Juru Tingkat I',
            'JURU TINGKAT 1' => 'Juru Tingkat I',
            'JURU TK 1' => 'Juru Tingkat I',
            'JURU TK. 1' => 'Juru Tingkat I',
            'JURU I' => 'Juru Tingkat I',
            'JURU 1' => 'Juru Tingkat I',

            // Juru Muda Tingkat I (JURDATU / JURDA I)
            'JURDATU' => 'Juru Muda Tingkat I',
            'JURDA I' => 'Juru Muda Tingkat I',
            'JURDA 1' => 'Juru Muda Tingkat I',
            'JURDA TK.I' => 'Juru Muda Tingkat I',
            'JURDA TK.1' => 'Juru Muda Tingkat I',
            'JUR DATU' => 'Juru Muda Tingkat I',
            'JURU MD TK I' => 'Juru Muda Tingkat I',
            'JURU MUDA TK.I' => 'Juru Muda Tingkat I',
            'JURU MUDA TK.1' => 'Juru Muda Tingkat I',
            'JURU MUDA TK. I' => 'Juru Muda Tingkat I',
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
     * Mapping golongan PNS ke nama pangkat resmi.
     * Berdasarkan Urutan Pangkat dan Golongan PNS Lengkap.
     *
     * Golongan I (Juru):
     *   Ia  = Juru Muda
     *   Ib  = Juru Muda Tingkat I
     *   Ic  = Juru
     *   Id  = Juru Tingkat I
     *
     * Golongan II (Pengatur):
     *   IIa = Pengatur Muda
     *   IIb = Pengatur Muda Tingkat I
     *   IIc = Pengatur
     *   IId = Pengatur Tingkat I
     *
     * Golongan III (Penata):
     *   IIIa = Penata Muda
     *   IIIb = Penata Muda Tingkat I
     *   IIIc = Penata
     *   IIId = Penata Tingkat I
     *
     * Golongan IV (Pembina):
     *   IVa = Pembina
     *   IVb = Pembina Tingkat I
     *   IVc = Pembina Utama Muda
     *   IVd = Pembina Utama Madya
     *   IVe = Pembina Utama
     */
    public static function getGolonganToRankMap(): array
    {
        return [
            // ── Golongan I (Juru) ──
            'IA' => 'Juru Muda',
            'IB' => 'Juru Muda Tingkat I',
            'IC' => 'Juru',
            'ID' => 'Juru Tingkat I',
            // Alias angka
            '1A' => 'Juru Muda',
            '1B' => 'Juru Muda Tingkat I',
            '1C' => 'Juru',
            '1D' => 'Juru Tingkat I',

            // ── Golongan II (Pengatur) ──
            'IIA' => 'Pengatur Muda',
            'IIB' => 'Pengatur Muda Tingkat I',
            'IIC' => 'Pengatur',
            'IID' => 'Pengatur Tingkat I',
            // Alias angka
            '2A' => 'Pengatur Muda',
            '2B' => 'Pengatur Muda Tingkat I',
            '2C' => 'Pengatur',
            '2D' => 'Pengatur Tingkat I',

            // ── Golongan III (Penata) ──
            'IIIA' => 'Penata Muda',
            'IIIB' => 'Penata Muda Tingkat I',
            'IIIC' => 'Penata',
            'IIID' => 'Penata Tingkat I',
            // Alias angka
            '3A' => 'Penata Muda',
            '3B' => 'Penata Muda Tingkat I',
            '3C' => 'Penata',
            '3D' => 'Penata Tingkat I',

            // ── Golongan IV (Pembina) ──
            'IVA' => 'Pembina',
            'IVB' => 'Pembina Tingkat I',
            'IVC' => 'Pembina Utama Muda',
            'IVD' => 'Pembina Utama Madya',
            'IVE' => 'Pembina Utama',
            // Alias angka
            '4A' => 'Pembina',
            '4B' => 'Pembina Tingkat I',
            '4C' => 'Pembina Utama Muda',
            '4D' => 'Pembina Utama Madya',
            '4E' => 'Pembina Utama',
        ];
    }

    /**
     * Normalisasi string golongan dari Excel ke format standar (uppercase, tanpa spasi/titik)
     * Contoh: "II.d" -> "IID", "III/a" -> "IIIA", "2 a" -> "2A"
     */
    private static function normalizeGolongan(string $golongan): string
    {
        $g = strtoupper(trim($golongan));
        // Hapus karakter pemisah: titik, garis miring, spasi
        $g = preg_replace('/[\s.\/\-]+/', '', $g);

        return $g;
    }

    /**
     * Cek apakah input terlihat seperti pangkat PNS (bukan POLRI)
     */
    private static function looksLikePnsRank(string $upperInput): bool
    {
        $pnsKeywords = ['PEMB', 'PENAT', 'PENDA', 'PENGAT', 'PENGD', 'JURU', 'JURDA', 'PNTA', 'PPPK', 'P3K'];
        foreach ($pnsKeywords as $kw) {
            if (str_starts_with($upperInput, $kw)) {
                return true;
            }
        }

        return false;
    }

    public static function findRankWithCorrection(string $rankName, Collection &$ranks, string $golongan = '', bool $isSiswaSatker = false): array
    {
        $upperInput = strtoupper(trim($rankName));

        // Bersihkan embel-embel golongan PNS jika menempel di nama pangkat (misal: "PENATA/IIIC", "PENGATUR TK.I/II.D")
        // Tapi simpan informasi golongan yang diekstrak jika belum ada golongan dari kolom Excel
        $extractedGolongan = '';
        if (preg_match('/\s*\/?\s*((IV|III|II|I)\.?\s*[A-E])\s*$/i', $upperInput, $golMatch)) {
            $extractedGolongan = $golMatch[1];
            $upperInput = preg_replace('/\s*\/?\s*(IV|III|II|I)\.?\s*[A-E]\s*$/i', '', $upperInput);
            $upperInput = trim($upperInput);
        }

        // Gunakan golongan dari kolom Excel, atau yang diekstrak dari nama pangkat
        $effectiveGolongan = ! empty($golongan) ? $golongan : $extractedGolongan;
        $normalizedGolongan = self::normalizeGolongan($effectiveGolongan);

        // --- KHUSUS PPPK ---
        $cleanInput = trim($upperInput, '.');
        if ($cleanInput === 'PPPK' || $cleanInput === 'P3K') {
            $upperInput = 'PPPK';
        }

        $correctionMap = self::getPangkatCorrectionMap();
        $golonganMap = self::getGolonganToRankMap();

        // ═══════════════════════════════════════════════════════════
        // STRATEGI 1: GOLONGAN-BASED RESOLUTION (paling akurat)
        // Jika ada golongan dan input terlihat seperti PNS, langsung
        // tentukan pangkat dari golongan.
        // ═══════════════════════════════════════════════════════════
        if (! empty($normalizedGolongan) && isset($golonganMap[$normalizedGolongan])) {
            $isPnsLike = self::looksLikePnsRank($upperInput) || empty($upperInput);

            if ($isPnsLike) {
                $golonganRankName = $golonganMap[$normalizedGolongan];
                $rank = self::findRankByName($golonganRankName, $ranks);
                if ($rank) {
                    $isCorrected = (strtoupper($rank->name) !== $upperInput);

                    return [
                        'rank' => $rank,
                        'corrected' => $isCorrected,
                        'original' => $rankName,
                        'corrected_to' => $isCorrected ? $rank->name : null,
                    ];
                }
            }
        }

        // ═══════════════════════════════════════════════════════════
        // STRATEGI 2: EXACT MATCH (nama pangkat persis ada di DB)
        // ═══════════════════════════════════════════════════════════
        $rank = $ranks->get($upperInput);
        if ($rank) {
            return ['rank' => $rank, 'corrected' => false, 'original' => $rankName, 'corrected_to' => null];
        }

        // --- AUTO-CREATE UNTUK SATKER SISWA ---
        // Jika satker adalah SISWA, KITA LEWATI SEMUA KOREKSI (Correction Map & Levenshtein)
        // dan langsung buat rank baru sesuai input asli dari Excel (AKPOL tetap AKPOL, dll)
        if ($isSiswaSatker && ! empty($upperInput)) {
            $newRank = \App\Models\Rank::create([
                'name' => $upperInput,
                'category' => 'SISWA',
                'sort_order' => 50,
            ]);
            $ranks->put(strtoupper($upperInput), $newRank);

            return ['rank' => $newRank, 'corrected' => false, 'original' => $rankName, 'corrected_to' => null];
        }

        // ═══════════════════════════════════════════════════════════
        // STRATEGI 3: CORRECTION MAP (typo umum)
        // ═══════════════════════════════════════════════════════════
        if (isset($correctionMap[$upperInput])) {
            $correctedName = $correctionMap[$upperInput];

            // Jika ada golongan, validasi apakah koreksi map sesuai dengan golongan
            // Jika tidak sesuai, gunakan golongan sebagai sumber kebenaran
            if (! empty($normalizedGolongan) && isset($golonganMap[$normalizedGolongan])) {
                $golonganRankName = $golonganMap[$normalizedGolongan];
                if (strtolower($correctedName) !== strtolower($golonganRankName)) {
                    // Golongan mengatakan pangkat berbeda dari correction map
                    // Prioritaskan golongan karena lebih akurat
                    $rank = self::findRankByName($golonganRankName, $ranks);
                    if ($rank) {
                        return ['rank' => $rank, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $rank->name];
                    }
                }
            }

            $rank = self::findRankByName($correctedName, $ranks);
            if ($rank) {
                return ['rank' => $rank, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $rank->name];
            }
        }

        // ═══════════════════════════════════════════════════════════
        // STRATEGI 4: LEVENSHTEIN (fuzzy match, max distance 2)
        // Untuk PNS, batasi hanya ke pangkat PNS agar tidak salah
        // ═══════════════════════════════════════════════════════════
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;
        $isPnsLike = self::looksLikePnsRank($upperInput);

        foreach ($ranks as $key => $r) {
            // Jika input terlihat PNS, hanya bandingkan dengan pangkat PNS
            if ($isPnsLike && isset($r->category) && $r->category !== 'PNS') {
                continue;
            }
            $dist = levenshtein($upperInput, strtoupper($key));
            if ($dist < $bestDistance) {
                $bestDistance = $dist;
                $bestMatch = $r;
            }
        }

        if ($bestDistance <= 2 && $bestMatch) {
            // Jika ada golongan, sekali lagi validasi terhadap golongan
            if (! empty($normalizedGolongan) && isset($golonganMap[$normalizedGolongan])) {
                $golonganRankName = $golonganMap[$normalizedGolongan];
                $golonganRank = self::findRankByName($golonganRankName, $ranks);
                if ($golonganRank) {
                    return ['rank' => $golonganRank, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $golonganRank->name];
                }
            }

            return ['rank' => $bestMatch, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $bestMatch->name];
        }

        // ═══════════════════════════════════════════════════════════
        // FALLBACK: Jika masih tidak ketemu tapi ada golongan, gunakan golongan
        // ═══════════════════════════════════════════════════════════
        if (! empty($normalizedGolongan) && isset($golonganMap[$normalizedGolongan])) {
            $golonganRankName = $golonganMap[$normalizedGolongan];
            $rank = self::findRankByName($golonganRankName, $ranks);
            if ($rank) {
                return ['rank' => $rank, 'corrected' => true, 'original' => $rankName, 'corrected_to' => $rank->name];
            }
        }

        return ['rank' => null, 'corrected' => false, 'original' => $rankName, 'corrected_to' => null];
    }

    /**
     * Helper: cari Rank dari collection berdasarkan nama (case-insensitive)
     */
    private static function findRankByName(string $name, Collection $ranks): ?Rank
    {
        // Coba exact match dulu (uppercase)
        $rank = $ranks->get(strtoupper($name));
        if ($rank) {
            return $rank;
        }
        // Case-insensitive fallback
        foreach ($ranks as $key => $r) {
            if (strtolower($key) === strtolower($name)) {
                return $r;
            }
        }

        return null;
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
     * Deteksi apakah file Excel punya 2 kolom NO (double-NO).
     * Beberapa satker menambahkan kolom NO ekstra di kolom A, sehingga semua kolom data bergeser 1.
     * Contoh: Kolom A = NO grup, Kolom B = NO urut, Kolom C = NAMA, ...
     *
     * @return int Offset kolom (0 = standar, 1 = double-NO)
     */
    private static function detectColumnOffset(Collection $rows): int
    {
        $doubleNoScore = 0;
        $normalScore = 0;
        $sampled = 0;

        foreach ($rows as $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            $col0 = trim((string) ($row[0] ?? ''));
            $col1 = trim((string) ($row[1] ?? ''));
            $col2 = trim((string) ($row[2] ?? ''));

            // Skip baris kosong atau header
            if (empty($col0) && empty($col1) && empty($col2)) {
                continue;
            }

            // Jika col0 numeric DAN col1 numeric DAN col2 teks (nama) → double-NO
            if (is_numeric($col0) && is_numeric($col1) && ! empty($col2) && ! is_numeric($col2) && strlen($col2) >= 2) {
                $doubleNoScore++;
            }
            // Jika col0 numeric DAN col1 teks (nama) → standar
            elseif (is_numeric($col0) && ! empty($col1) && ! is_numeric($col1) && strlen($col1) >= 2) {
                $normalScore++;
            }

            $sampled++;
            if ($sampled >= 30) {
                break;
            } // Cukup sample 30 baris
        }

        return ($doubleNoScore > $normalScore && $doubleNoScore >= 3) ? 1 : 0;
    }

    /**
     * Parse rows menjadi array preview data (TANPA menyimpan ke DB).
     * Digunakan untuk menampilkan preview sebelum konfirmasi import.
     */
    public function generatePreview(Collection $rows, int $sheetIndex = 0): array
    {
        $ranks = Rank::all()->keyBy(fn ($r) => strtoupper($r->name));
        $preview = [];
        $seenNrps = []; // Track NRPs untuk deteksi duplikat dalam batch
        $sizeSanitizer = app(\App\Services\KaporRequirementService::class);

        // Sheet 0 = POLRI, Sheet >= 1 = PNS
        $sheetPersonnelType = $sheetIndex >= 1 ? 'PNS' : 'Polri';

        // Pre-load semua NRP yang sudah ada di database untuk deteksi cross-DB
        $existingNrpData = Personnel::whereNotNull('nrp')
            ->where('nrp', '!=', '')
            ->with('satker:id,name')
            ->get(['id', 'nrp', 'full_name', 'satker_id'])
            ->keyBy('nrp');

        $isSiswaSatker = false;
        if ($this->satkerId) {
            $satker = \App\Models\Satker::find($this->satkerId);
            if ($satker && strtoupper($satker->code) === 'SISWA') {
                $isSiswaSatker = true;
            }
        }

        // Auto-detect apakah file punya 2 kolom NO (double-NO)
        $offset = self::detectColumnOffset($rows);

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            // Terapkan offset kolom: jika double-NO, semua index geser +1
            $colName = 1 + $offset;
            $colRank = 2 + $offset;
            $colGol = 3 + $offset;
            $colNrp = 4 + $offset;
            $colJabatan = 5 + $offset;
            $colBagian = 6 + $offset;
            $colGender = 7 + $offset;
            $colKet = 17 + $offset;
            $colKet2 = 18 + $offset;
            $colKet3 = 19 + $offset;
            $colKet4 = 20 + $offset;

            // Bersihkan tanda bintang (*) dan normalisasi enter/spasi ganda (karena sering ada \n di dalam cell Excel)
            $fullNameRtn = str_replace('*', '', $row[$colName] ?? '');
            $fullName = trim(preg_replace('/\s+/', ' ', $fullNameRtn));

            $rankInputRtn = str_replace('*', '', $row[$colRank] ?? '');
            $rankInput = trim(preg_replace('/\s+/', ' ', $rankInputRtn));

            $golongan = trim($row[$colGol] ?? '');
            $nrpRaw = $row[$colNrp] ?? '';

            // ── NRP/NIP Precision Fix ──
            // Jika format cell Excel awalnya General/Number (bukan Text) dengan panjang NIP > 15 digit (spt 18 digit),
            // PhpSpreadsheet / toCollection akan membacanya sebagai float/int yang termutiasi
            // karena limit presisi IEEE 754 (contoh: 197303052008101000 murni dibaca PHP sbg int 197303052008100992).
            // Namun Excel UI menampilkan 15 significant digits (trailing zero).
            // Logic ini secara matematis mengembalikan angka termutasi tsb kembali ke versi 15-sig display di Excel.
            if ((is_int($nrpRaw) || is_float($nrpRaw)) && $nrpRaw >= 1000000000000000) {
                $strNrp = sprintf('%.15g', (float) $nrpRaw);
                // Menjadi '1.97303052008101e+17' (membuang sisa digit termutasi di ujung)
                if (stripos($strNrp, 'e') !== false) {
                    $parts = explode('e', strtolower($strNrp));
                    $base = str_replace('.', '', $parts[0]);
                    $exp = (int) $parts[1];
                    $nrp = str_pad($base, $exp + 1, '0', STR_PAD_RIGHT);
                } else {
                    $nrp = str_replace('.', '', $strNrp);
                }
            } else {
                // Untuk teks literal asli (format cell Text) atau angka normal < 15 digit
                $trimNrp = trim((string) $nrpRaw);
                if (is_numeric($trimNrp) && stripos($trimNrp, 'E') !== false) {
                    $nrp = number_format((float) $trimNrp, 0, '', '');
                } else {
                    $nrp = $trimNrp;
                }
            }
            $jabatan = trim($row[$colJabatan] ?? '');
            $bagian = trim($row[$colBagian] ?? '');
            $genderRaw = strtoupper(trim($row[$colGender] ?? ''));
            $keterangan = trim($row[$colKet] ?? '');
            $keterangan2 = trim($row[$colKet2] ?? '');
            $keterangan3 = trim($row[$colKet3] ?? '');
            $keterangan4 = trim($row[$colKet4] ?? '');

            // Ambil nomor urut dari kolom pertama (NO)
            $no = trim($row[0] ?? '');

            // Jika Nama kosong, dipastikan baris tidak valid/kosong/rekap
            if (empty($fullName)) {
                continue;
            }

            // Jika pangkat kosong, cek apakah baris punya nomor urut (kolom NO)
            // Personel asli selalu punya nomor urut (1, 2, 3, ...)
            // Baris header/catatan (TUTUP KEPALA, BAJU PRIA, dll) tidak punya nomor urut
            // Untuk double-NO, cek kedua kolom NO
            // Tambahan: NO yg berisi rumus Excel (=A23+1) juga valid — artinya baris personel
            $hasNumericNo = is_numeric($no)
                || ($offset > 0 && is_numeric(trim($row[1] ?? '')))
                || str_starts_with($no, '=');
            if (empty($rankInput) && ! $hasNumericNo) {
                continue;
            }

            // --- FILTER BARIS KOTOR / REKAPITULASI ---
            $nameLower = strtolower($fullName);

            // 1. Abaikan baris jika Nama diawali '=' (rumus excel) atau adalah string "jumlah"/"total"
            if (str_starts_with($fullName, '=')) {
                continue;
            }
            // Cegah false positive, misal ada orang bernama "Total", hanya tolak bila namanya persis "JUMLAH" atau "TOTAL"
            if ($nameLower === 'jumlah' || $nameLower === 'total' || $nameLower === 'dst' || $nameLower === 'dst.') {
                continue;
            }

            // 1a2. Abaikan baris header kategori ukuran kapor (TUTUP KEPALA, KEMEJA, CELANA, dll)
            // Di beberapa satker (RUMKIT), baris ini muncul di area data personel dengan NO numerik
            // dan kolom pangkat terisi kode ukuran (PRIA, 14=2, K=3, dll) — bukan data personel asli.
            $kaporCategories = [
                'tutup kepala', 'tutup badan', 'tutup kaki',
                'kemeja', 'kemeja pria', 'kemeja wanita',
                'celana', 'celana pria', 'celana wanita',
                'baju', 'baju pria', 'baju wanita',
                'sepatu', 'sepatu pdh', 'sepatu pdl', 'sepatu pdh/pdl',
                'sepatu olahraga', 'sepatu pria pdh', 'wanita pria pdh',
                'sepatu pria olahraga', 'wanita pria olahraga',
                't-shirt', 't shirt', 't-shirt/olahraga', 't shirt/olahraga',
                't-shirt pria', 't-shirt wanita', 't shirt pria', 't shirt wanita',
                't-shirt/olahraga pria dan wanita', 't-shirt/olahraga pria & wanita',
                'jaket', 'sabuk', 'jilbab',
                'topi bintara', 'topi', 'kaos kaki',
            ];
            if (in_array($nameLower, $kaporCategories)) {
                continue;
            }

            // 1b. Abaikan baris rekapitulasi: jika golongan berisi "JUMLAH" atau "TOTAL"
            // Baris rekap biasanya: Nama="SD"/"JUMLAH", Golongan="JUMLAH" — ini bukan data personel
            $golLower = strtolower($golongan);
            if ($golLower === 'jumlah' || $golLower === 'total' || $golLower === 'sub total' || $golLower === 'sub jumlah') {
                continue;
            }

            // 1c. Abaikan baris rekapitulasi: jika keterangan (KET) atau jenis kelamin (JK) berisi "JUMLAH"/"TOTAL"
            // Di beberapa satker (DIT Tahti), baris rekap ukuran kapor punya:
            // - Layout pendek (8 kolom): Nama="SD", JK="JUMLAH" (karena JUMLAH ada di indeks kolom gender)
            // - Layout penuh: KET="JUMLAH"
            $ketLower = strtolower($keterangan);
            $genderLower = strtolower($genderRaw);
            if ($ketLower === 'jumlah' || $ketLower === 'total' || $ketLower === 'sub total' || $ketLower === 'sub jumlah'
                || $genderLower === 'jumlah' || $genderLower === 'total' || $genderLower === 'sub total' || $genderLower === 'sub jumlah') {
                continue;
            }

            // 1d. Abaikan baris jika TIDAK ADA DATA di kolom identitas (Pangkat, Golongan, NRP, Jabatan, Bagian, JK)
            // Sesuai permintaan user: jika kolom-kolom ini kosong, berarti bukan data personil (biasanya baris header/kategori).
            $rankIsPlaceholder = empty($rankInput) || preg_match('/^[\-\.]+$/', $rankInput) || (is_numeric($rankInput) && strlen($rankInput) <= 3);
            if (empty($nrp) && $rankIsPlaceholder && empty($jabatan) && empty($golongan) && empty($bagian) && empty($genderRaw)) {
                continue;
            }

            // 2. Abaikan baris jika Nama SANGAT PENDEK / berupa angka (seperti baris rekap -> Nama "14", "15", "K")
            // Nama personel asli umumnya >= 2 huruf dan tidak cuma terdiri dari angka.
            if (strlen($fullName) < 2 || is_numeric(str_replace([' ', '.', ','], '', $fullName))) {
                continue;
            }

            // 3. Pengecekan NRP/NIP
            // Normalisasi: dash/strip saja (-, --, ---) dianggap kosong (bukan NRP asli)
            if (preg_match('/^[\-\.]+$/', $nrp)) {
                $nrp = '';
            }
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

                // NRP terlampau pendek (< 4 karakter) dianggap bukan NRP valid
                // Bersihkan saja, JANGAN skip baris — personel tetap perlu diimport
                if (strlen($nrp) < 4) {
                    $nrp = '';
                }
            }

            // Gender: P di Excel → L (Laki-laki), W di Excel → P (Perempuan)
            $gender = ($genderRaw === 'W') ? 'P' : 'L';

            // Koreksi pangkat (sekarang menyertakan golongan untuk PPPK)
            $rankResult = self::findRankWithCorrection($rankInput, $ranks, $golongan, $isSiswaSatker);

            // Ukuran kapor (kolom I-Q, index 8-16, ditambah offset jika double-NO)
            $sizes = $sizeSanitizer->sanitizeSubmittedSizes([
                'topi' => trim($row[8 + $offset] ?? ''),
                'kemeja' => trim($row[9 + $offset] ?? ''),
                'celana' => trim($row[10 + $offset] ?? ''),
                'olahraga' => trim($row[11 + $offset] ?? ''),
                'sepatu_dinas' => trim($row[12 + $offset] ?? ''),
                'sepatu_olahraga' => trim($row[13 + $offset] ?? ''),
                'jaket' => trim($row[14 + $offset] ?? ''),
                'sabuk' => trim($row[15 + $offset] ?? ''),
                'jilbab' => trim($row[16 + $offset] ?? ''),
            ], $gender);

            // Pria tidak butuh jilbab — hapus dari data ukuran
            $status = 'ok';
            $incompleteFields = [];

            // Cek kelengkapan data — kumpulkan semua field yang kosong
            // Pangkat berupa dash (-, --) atau angka saja (57, 8) dianggap kosong, bukan error
            $rankIsPlaceholder = preg_match('/^[\-\.]+$/', $rankInput) || (is_numeric($rankInput) && strlen($rankInput) <= 3);
            if (! $rankResult['rank'] && ! empty($rankInput) && ! $rankIsPlaceholder) {
                $status = 'error'; // pangkat diisi tapi tidak dikenali → perlu pilih manual
            } elseif (! $rankResult['rank']) {
                // Pangkat kosong atau placeholder → tandai sebagai belum lengkap (bisa diedit admin nanti)
                $incompleteFields[] = 'Pangkat';
            }
            if (empty($nrp)) {
                $incompleteFields[] = 'NRP/NIP';
            }

            // Deteksi NRP duplikat dalam batch yang sama
            $isDuplicateNrp = false;
            $dbDuplicate = null;
            if (! empty($nrp)) {
                // 1) Duplikat dalam batch (file Excel)
                if (isset($seenNrps[$nrp])) {
                    $isDuplicateNrp = true;
                    if ($status !== 'error') {
                        $status = 'corrected';
                    }
                    $dupeInfo = $seenNrps[$nrp];
                    $dupeRow = $dupeInfo['row_num'];
                    $dupeLabel = "NRP duplikat dengan baris #{$dupeRow}";
                    if ($rankResult['corrected_to']) {
                        $rankResult['corrected_to'] .= ' | '.$dupeLabel;
                    } else {
                        $rankResult['corrected'] = true;
                        $rankResult['corrected_to'] = $dupeLabel;
                    }

                    // Tandai juga baris pertama yang ditemukan sebelumnya agar ikut merah
                    $prevIdx = $dupeInfo['preview_idx'];
                    if (isset($preview[$prevIdx])) {
                        $preview[$prevIdx]['duplicate_nrp'] = true;
                        if ($preview[$prevIdx]['status'] !== 'error') {
                            $preview[$prevIdx]['status'] = 'corrected';
                        }
                    }
                } else {
                    $seenNrps[$nrp] = ['row_num' => $rowIndex + 11, 'preview_idx' => count($preview)];
                }

                // 2) Duplikat terhadap database (cross-satker)
                if (isset($existingNrpData[$nrp])) {
                    $existingP = $existingNrpData[$nrp];
                    $existingSatkerName = $existingP->satker->name ?? 'Satker tidak diketahui';
                    $isSameSatker = ($existingP->satker_id == $this->satkerId);
                    $dbDuplicate = [
                        'personnel_id' => $existingP->id,
                        'full_name' => $existingP->full_name,
                        'satker_name' => $existingSatkerName,
                        'satker_id' => $existingP->satker_id,
                        'same_satker' => $isSameSatker,
                    ];
                }
            }

            // Jika pangkat dikoreksi otomatis (misal PPPK, KOMBES POL, dll)
            if ($rankResult['corrected'] && $status !== 'error') {
                $status = 'corrected';
            }

            // Jika ada field yang belum lengkap, masukkan ke Auto Koreksi agar admin bisa lihat
            if (! empty($incompleteFields) && $status !== 'error') {
                $status = 'corrected';
                // Tambahkan info field yang kosong ke corrected_to
                $missingLabel = '— (Belum Lengkap: '.implode(', ', $incompleteFields).')';
                if ($rankResult['corrected_to']) {
                    // Sudah ada koreksi pangkat, tambahkan info missing
                    $rankResult['corrected_to'] .= ' | '.implode(', ', $incompleteFields).' kosong';
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
                'keterangan_2' => $keterangan2,
                'keterangan_3' => $keterangan3,
                'keterangan_4' => $keterangan4,
                'sizes' => $sizes,
                'status' => $status, // 'ok' | 'corrected' | 'error'
                'incomplete_fields' => $incompleteFields, // field yang kosong
                'duplicate_nrp' => $isDuplicateNrp, // true jika NRP duplikat dalam file
                'db_duplicate' => $dbDuplicate, // info duplikat dari database (null jika tidak ada)
                'personnel_type' => $sheetPersonnelType, // Polri atau PNS berdasarkan sheet Excel
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
        ini_set('memory_limit', '2G');

        $satker = Satker::findOrFail($satkerId);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $sizeMapping = ['topi', 'kemeja', 'celana', 'olahraga', 'sepatu_dinas', 'sepatu_olahraga', 'jaket', 'sabuk', 'jilbab'];
        $sizeSanitizer = app(\App\Services\KaporRequirementService::class);

        // ── Pre-load sekali ─────────────────────────────────────────────────
        $ranksById = Rank::all()->keyBy('id');

        // Kumpulkan semua NRP yang sudah ada di tabel users (UNIQUE constraint)
        // agar bisa deteksi bentrok dan buat TEMP nrp_nip kalau perlu
        $allNrp = collect($rows)->pluck('nrp')->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
        $usedNrpNip = User::whereIn('nrp_nip', $allNrp)->pluck('nrp_nip')->flip();

        // Track NRP yang sudah dipakai dalam batch ini (cegah UNIQUE collision)
        $batchUsedNrpNip = [];

        // ── Satu transaksi besar untuk semua insert ──────────────────────────
        DB::transaction(function () use (
            $rows, $satker, $ranksById, $usedNrpNip, &$batchUsedNrpNip, $sizeSanitizer, &$successCount, &$errorCount, &$errors
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
                $keterangan2 = trim($data['keterangan_2'] ?? '');
                $keterangan3 = trim($data['keterangan_3'] ?? '');
                $keterangan4 = trim($data['keterangan_4'] ?? '');
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
                    $isEmptyNrp = empty($nrp);

                    // ── Tentukan nrp_nip untuk user account ──────────────────
                    // users.nrp_nip UNIQUE → jika NRP sudah dipakai (di DB atau batch ini),
                    // buat TEMP agar tidak bentrok. Personnel tetap simpan NRP asli.
                    $nrpNipForUser = $nrp;
                    if ($isEmptyNrp || $usedNrpNip->has($nrp) || isset($batchUsedNrpNip[$nrp])) {
                        $nrpNipForUser = 'TEMP-'.strtoupper(substr(md5($fullName.$idx.microtime(true)), 0, 8));
                    }

                    // Tandai NRP ini sudah dipakai dalam batch
                    if (! $isEmptyNrp) {
                        $batchUsedNrpNip[$nrp] = true;
                    }

                    // ── Buat user baru ────────────────────────────────────────
                    $user = User::create([
                        'name' => $fullName,
                        'nrp_nip' => $nrpNipForUser,
                        'password' => password_hash($nrpNipForUser, PASSWORD_BCRYPT, ['cost' => 4]),
                        'satker_id' => $satker->id,
                        'is_active' => true,
                    ]);
                    $user->assignRole('personil');

                    // ── Personnel type ditentukan dari SHEET asal di Excel ────
                    // Sheet 0 = Polri, Sheet >= 1 = PNS (sudah di-set saat preview)
                    $personnelType = $data['personnel_type'] ?? 'Polri';

                    // ── Buat personnel baru ───────────────────────────────────
                    // personnels.nrp TIDAK unique → simpan NRP asli (boleh duplikat)
                    $hasNrpIssue = ! empty($data['db_duplicate']) || ! empty($data['duplicate_nrp']);

                    $personnel = Personnel::create([
                        'user_id' => $user->id,
                        'nrp' => $isEmptyNrp ? null : $nrp,
                        'full_name' => $fullName,
                        'rank_id' => $rank ? $rank->id : null,
                        'satker_id' => $satker->id,
                        'jabatan' => $jabatan,
                        'bagian' => $bagian,
                        'personnel_type' => $personnelType,
                        'gender' => $gender,
                        'golongan' => $golongan,
                        'keterangan' => $keterangan,
                        'keterangan_2' => $keterangan2 ?: null,
                        'keterangan_3' => $keterangan3 ?: null,
                        'keterangan_4' => $keterangan4 ?: null,
                        'is_active' => true,
                        'has_nrp_issue' => $hasNrpIssue,
                        'nrp_issue_note' => $this->buildNrpIssueNote($data),
                    ]);

                    // Simpan ukuran kapor
                    $personnel->kapor_sizes = $sizeSanitizer->sanitizeSubmittedSizes($sizes, $gender);
                    $personnel->save();

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Baris {$idx} (NRP: {$nrp}): ".$e->getMessage();
                    \Illuminate\Support\Facades\Log::error("[IMPORT ERROR] Baris={$idx} NRP={$nrp} Name={$fullName} Err=".$e->getMessage());
                }
            }
        });

        self::recalculateSatkerCount($satkerId);

        // DEBUG LOG — sementara untuk trace masalah import cross-satker
        $totalRows = count($rows);
        $dbDupCount = collect($rows)->filter(fn ($r) => ! empty($r['db_duplicate']))->count();
        $batchDupCount = collect($rows)->filter(fn ($r) => ! empty($r['duplicate_nrp']))->count();
        \Illuminate\Support\Facades\Log::info("[IMPORT DEBUG] Satker={$satkerId} Total={$totalRows} Success={$successCount} Error={$errorCount} DbDup={$dbDupCount} BatchDup={$batchDupCount}");

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * Helper: buat catatan masalah NRP untuk personel yang terdeteksi duplikat.
     */
    private function buildNrpIssueNote(array $data): ?string
    {
        $notes = [];

        if (! empty($data['db_duplicate'])) {
            $db = $data['db_duplicate'];
            $loc = $db['same_satker'] ? 'satker ini' : $db['satker_name'];
            $notes[] = "NRP duplikat dengan {$db['full_name']} di {$loc}";
        }

        if (! empty($data['duplicate_nrp'])) {
            $notes[] = 'NRP duplikat dalam file import';
        }

        return ! empty($notes) ? implode('; ', $notes) : null;
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
        $sizeSanitizer = app(\App\Services\KaporRequirementService::class);

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
                DB::transaction(function () use ($row, $nrp, $fullName, $rank, $satker, $jabatan, $bagian, $gender, $golongan, $keterangan, $sizeMapping, $sizeSanitizer) {
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
                    $rawSizes = [];
                    foreach ($sizeMapping as $colIndex => $key) {
                        $sizeVal = trim($row[$colIndex] ?? '');
                        $rawSizes[$key] = $sizeVal;
                    }

                    // Pria tidak butuh jilbab — hapus dari kapor_sizes
                    $kaporSizes = array_merge(
                        $kaporSizes,
                        $sizeSanitizer->sanitizeSubmittedSizes($rawSizes, $gender),
                    );

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
