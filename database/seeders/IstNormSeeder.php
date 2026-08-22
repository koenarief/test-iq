<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IstNormSubtest;
use App\Models\IstNormTotal;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IstNormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tabel sebelum memasukkan data baru
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        IstNormSubtest::truncate();
        IstNormTotal::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Lokasi file Master Excel IST
        $filePath = storage_path('app/Skoring_Tes_IST_3_Sheet.xlsx');

        if (file_exists($filePath)) {
            $this->seedFromExcel($filePath);
        } else {
            // Fallback seeder jika file Excel tidak ditemukan
            $this->seedFallbackData();
        }
    }

    /**
     * Parsing dinamis langsung dari Sheet NORMA
     */
    private function seedFromExcel(string $filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('NORMA');
        $rows = $sheet->toArray();

        // ------------------------------------------------------------------
        // 1. SEED NORMA SUBTEST (Konversi RW -> SW per Kelompok Usia)
        // ------------------------------------------------------------------
        $subtestData = [];
        $subtests = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];
        
        // Kolom blok umur di sheet NORMA (Mulais: 13thn, 14thn, ..., >45thn)
        $ageBlocks = [
            26 => ['min' => 13, 'max' => 13],
            36 => ['min' => 14, 'max' => 14],
            46 => ['min' => 15, 'max' => 15],
            56 => ['min' => 16, 'max' => 16],
            66 => ['min' => 17, 'max' => 17],
            76 => ['min' => 18, 'max' => 18],
            86 => ['min' => 19, 'max' => 20],
            96 => ['min' => 21, 'max' => 24],
            106 => ['min' => 25, 'max' => 28],
            116 => ['min' => 29, 'max' => 33],
            126 => ['min' => 34, 'max' => 39],
            136 => ['min' => 40, 'max' => 45],
            146 => ['min' => 46, 'max' => 99],
        ];

        foreach ($ageBlocks as $startCol => $age) {
            // Row 5 s/d 25 mewakili Raw Score 0 s/d 20
            for ($r = 5; $r <= 25; $r++) {
                if (!isset($rows[$r][$startCol]) || $rows[$r][$startCol] === null) continue;

                $rw = (int) $rows[$r][$startCol];

                foreach ($subtests as $idx => $subtest) {
                    $swVal = $rows[$r][$startCol + 1 + $idx] ?? null;
                    if ($swVal !== null && is_numeric($swVal)) {
                        $subtestData[] = [
                            'min_age' => $age['min'],
                            'max_age' => $age['max'],
                            'subtest' => $subtest,
                            'raw_score' => $rw,
                            'standard_score' => (int) $swVal,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($subtestData, 100) as $chunk) {
            IstNormSubtest::insert($chunk);
        }

        // ------------------------------------------------------------------
        // 2. SEED NORMA GESAMT / TOTAL IQ (Konversi Total SW -> IQ & Kategori)
        // ------------------------------------------------------------------
        $totalData = [];
        $gesamtAgeCols = [
            28 => ['min' => 13, 'max' => 13],
            29 => ['min' => 14, 'max' => 14],
            30 => ['min' => 15, 'max' => 15],
            31 => ['min' => 16, 'max' => 16],
            32 => ['min' => 17, 'max' => 17],
            33 => ['min' => 18, 'max' => 18],
            34 => ['min' => 19, 'max' => 20],
            35 => ['min' => 21, 'max' => 24],
            36 => ['min' => 25, 'max' => 28],
            37 => ['min' => 29, 'max' => 33],
            38 => ['min' => 34, 'max' => 39],
            39 => ['min' => 40, 'max' => 45],
            40 => ['min' => 46, 'max' => 99],
        ];

        // Membaca baris Gesamt IQ (Baris 45 ke atas)
        for ($r = 45; $r < count($rows); $r++) {
            $totalSw = $rows[$r][27] ?? null;
            if ($totalSw === null || !is_numeric($totalSw)) continue;

            foreach ($gesamtAgeCols as $col => $age) {
                $iqVal = $rows[$r][$col] ?? null;
                if ($iqVal !== null && is_numeric($iqVal)) {
                    $iq = (int) $iqVal;
                    $totalData[] = [
                        'min_age' => $age['min'],
                        'max_age' => $age['max'],
                        'total_sw' => (int) $totalSw,
                        'iq_score' => $iq,
                        'iq_category' => $this->getIqCategory($iq),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($totalData, 100) as $chunk) {
            IstNormTotal::insert($chunk);
        }
    }

    /**
     * Menentukan Kategori Klasifikasi IQ Psikotes IST
     */
    private function getIqCategory(int $iq): string
    {
        if ($iq >= 130) return 'Very Superior';
        if ($iq >= 120) return 'Superior';
        if ($iq >= 110) return 'High Average';
        if ($iq >= 90)  return 'Average';
        if ($iq >= 80)  return 'Low Average';
        if ($iq >= 70)  return 'Borderline';
        return 'Mentally Defective';
    }

    /**
     * Fallback seeder jika file Excel belum tersedia di storage
     */
    private function seedFallbackData(): void
    {
        // Contoh data dasar untuk testing
        IstNormSubtest::create([
            'min_age' => 13, 'max_age' => 99, 'subtest' => 'SE',
            'raw_score' => 10, 'standard_score' => 100
        ]);

        IstNormTotal::create([
            'min_age' => 13, 'max_age' => 99, 'total_sw' => 100,
            'iq_score' => 100, 'iq_category' => 'Average'
        ]);
    }
}