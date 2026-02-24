<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use App\Models\KaporItem;
use App\Models\KaporSize;
use App\Models\KaporSubmission;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PersonnelImport implements ToCollection, WithStartRow
{
    protected $successCount = 0;
    protected $errorCount = 0;
    protected $errors = [];
    protected $satkerId;

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

    public function collection(Collection $rows)
    {
        $ranks = Rank::all()->keyBy(fn($r) => strtoupper($r->name));
        $satkers = Satker::all()->keyBy(fn($s) => strtoupper($s->name));
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        // Logical mapping of items to column indexes
        $itemMapping = [
            8 => 9, // Col I: TUTUP KEPALA (Item ID 9)
            9 => 1, // Col J: KEMEJA (Item ID 1)
            10 => 2, // Col K: CELANA / ROK (Item ID 2)
            11 => 3, // Col L: T.SHIRT / OLAHRAGA (Item ID 3)
            12 => 5, // Col M: SEPATU DINAS (Item ID 5)
            13 => 6, // Col N: SEPATU OLAHRAGA (Item ID 6)
            14 => 4, // Col O: JAKET (Item ID 4)
            15 => 7, // Col P: SABUK (Item ID 7)
            16 => 8, // Col Q: JILBAB (Item ID 8)
        ];

        // Cache all sizes for performance
        $allSizes = KaporSize::all()->groupBy('kapor_item_id');

        foreach ($rows as $rowIndex => $row) {
            // Ensure row is an array
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            // Values mapped by index
            $fullName = trim($row[1] ?? '');
            $rankName = strtoupper(trim($row[2] ?? ''));
            $golongan = trim($row[3] ?? '');
            $nrp = trim($row[4] ?? '');
            $jabatan = trim($row[5] ?? '');
            $bagian = trim($row[6] ?? '');
            $genderChar = strtoupper(trim($row[7] ?? '')); // P or W or L or P
            $keterangan = trim($row[17] ?? '');

            if (empty($nrp) || empty($fullName)) {
                continue;
            }

            // Map Gender: P -> L (Pria), W -> P (Wanita)
            $gender = ($genderChar === 'W' || $genderChar === 'P') ? ($genderChar === 'W' ? 'P' : 'L') : 'L';
            if ($genderChar === 'P' && $row[1] == 'DEDE RUHIAT') { // Handle specific P/W ambiguity if needed, but standard is P=Pria, W=Wanita in image
                // In the image, 'P' is used for Men (Dede Ruhiat is P). 'W' for Women.
                $gender = 'L';
            }
            if ($genderChar === 'W')
                $gender = 'P';
            else if ($genderChar === 'P')
                $gender = 'L';

            $rank = $ranks->get($rankName);
            if (!$rank) {
                // Try partial match if exact fails
                foreach ($ranks as $name => $r) {
                    if (strpos($rankName, $name) !== false || strpos($name, $rankName) !== false) {
                        $rank = $r;
                        break;
                    }
                }
            }

            if (!$rank) {
                $this->errorCount++;
                $this->errors[] = "Baris " . ($rowIndex + 11) . " (NRP: {$nrp}): Pangkat '{$rankName}' tidak ditemukan.";
                continue;
            }

            // Use the Satker ID provided in constructor
            $satker = Satker::find($this->satkerId) ?? Satker::first();

            try {
                DB::transaction(function () use ($row, $nrp, $fullName, $rank, $satker, $jabatan, $bagian, $gender, $golongan, $keterangan, $itemMapping, $allSizes, $fiscalYear) {
                    $personnel = Personnel::where('nrp', $nrp)->first();

                    if (!$personnel) {
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
                    }
                    else {
                        $personnel->update([
                            'full_name' => $fullName,
                            'rank_id' => $rank->id,
                            'satker_id' => $satker->id,
                            'jabatan' => $jabatan,
                            'bagian' => $bagian,
                            'golongan' => $golongan,
                            'keterangan' => $keterangan,
                        ]);
                    }

                    // Process Kapor Sizes based on column mapping keys
                    // Dictionary mapping column index to kapor_sizes key
                    $sizeMapping = [
                        8 => 'topi', // Col I: TUTUP KEPALA
                        9 => 'kemeja', // Col J: KEMEJA
                        10 => 'celana', // Col K: CELANA / ROK
                        11 => 'olahraga', // Col L: T.SHIRT / OLAHRAGA
                        12 => 'sepatu_dinas', // Col M: SEPATU DINAS
                        13 => 'sepatu_olahraga', // Col N: SEPATU OLAHRAGA
                        14 => 'jaket', // Col O: JAKET
                        15 => 'sabuk', // Col P: SABUK
                        16 => 'jilbab', // Col Q: JILBAB
                    ];

                    $kaporSizes = $personnel->kapor_sizes ?? [];

                    foreach ($sizeMapping as $colIndex => $key) {
                        $sizeVal = trim($row[$colIndex] ?? '');
                        if (!empty($sizeVal) && $sizeVal !== '-' && $sizeVal !== '0') {
                            $kaporSizes[$key] = $sizeVal;
                        }
                    }

                    // Save decoupled sizes
                    $personnel->kapor_sizes = $kaporSizes;
                    $personnel->save();
                });
                $this->successCount++;
            }
            catch (\Exception $e) {
                $this->errorCount++;
                $this->errors[] = "Baris " . ($rowIndex + 11) . " (NRP: {$nrp}): " . $e->getMessage();
            }
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
