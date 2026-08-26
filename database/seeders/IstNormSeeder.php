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

        // File norma paling lengkap (usia 13 s/d <60 tahun). Jatuh ke file lama
        // (hanya usia 13-24) bila belum ada, lalu ke data contoh minimal.
        $candidates = [
            storage_path('app/norma-ist.xlsx'),
            storage_path('app/Skoring_Tes_IST_3_Sheet.xlsx'),
        ];

        foreach ($candidates as $filePath) {
            if (file_exists($filePath)) {
                $this->seedFromExcel($filePath);

                return;
            }
        }

        $this->seedFallbackData();
    }

    /**
     * Parsing dinamis dari Sheet NORMA. Layout kedua file sumber yang pernah
     * ditemui (Skoring_Tes_IST_3_Sheet.xlsx dan norma-ist.xlsx) berbeda posisi
     * baris/kolom persis, tapi berbagi pola yang sama:
     *
     *  - Blok RW->SW per subtes: satu baris header berisi sel "RW" berulang
     *    (satu per blok usia) menandai kolom pertama tiap blok. Baris tepat
     *    di atasnya = label usia blok tsb, baris tepat di bawahnya = label
     *    9 subtes (SE, WA, AN, GE, RA, ZR, FA, WU, ME) berurutan, dan baris
     *    setelah itu = data RW 0-20 (satu kolom RW + 9 kolom SW per blok).
     *  - Tabel Gesamt/Total IQ ditandai sel berisi kata "gesamt". Baris tepat
     *    di bawahnya = header ("RW" pada kolom indeks Total SW), baris
     *    berikutnya = label usia per kolom, baris setelah itu = data (kolom
     *    indeks = Total SW, kolom lain = IQ untuk usia terkait).
     *
     * PENTING: "Total SW" pada tabel Gesamt adalah RATA-RATA (bukan jumlah)
     * dari 9 SW subtes, dibulatkan ke bilangan bulat terdekat -> jangkauan
     * nilainya sama dengan skala SW per subtes (puluhan-ratusan rendah),
     * bukan skala 500-1000+ seperti hasil penjumlahan 9 subtes. Dikonfirmasi
     * dengan menghitung ulang rata-rata SW dari data RW=20 dan mencocokkannya
     * ke baris tabel Gesamt yang bersangkutan.
     */
    private function seedFromExcel(string $filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('NORMA');
        $rows = $sheet->toArray();

        $this->seedSubtestNorms($rows);
        $this->seedGesamtNorms($rows);
    }

    private function seedSubtestNorms(array $rows): void
    {
        $subtests = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];

        $headerRow = $this->findRowWithCellValue($rows, 'RW', 0, 10);

        if ($headerRow === null) {
            return;
        }

        $ageLabelRow = $headerRow - 1;
        $dataStartRow = $headerRow + 2;

        $subtestData = [];

        foreach ($this->findColumnsWithCellValue($rows[$headerRow], 'RW') as $startCol) {
            $age = $this->parseAgeLabel((string) ($rows[$ageLabelRow][$startCol] ?? ''));

            if ($age === null) {
                continue;
            }

            for ($r = $dataStartRow; $this->isNumericCell($rows[$r][$startCol] ?? null); $r++) {
                $rw = (int) $rows[$r][$startCol];

                foreach ($subtests as $idx => $subtest) {
                    $swVal = $rows[$r][$startCol + 1 + $idx] ?? null;

                    if ($this->isNumericCell($swVal)) {
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
    }

    private function seedGesamtNorms(array $rows): void
    {
        $titleRow = $this->findRowContaining($rows, 'gesamt');

        if ($titleRow === null) {
            return;
        }

        $headerRow = $titleRow + 1;
        $ageLabelRow = $titleRow + 2;
        $dataStartRow = $titleRow + 3;

        $totalSwCol = $this->findColumnsWithCellValue($rows[$headerRow] ?? [], 'RW')[0] ?? null;

        if ($totalSwCol === null) {
            return;
        }

        $gesamtAgeCols = [];

        foreach ($rows[$ageLabelRow] ?? [] as $col => $label) {
            if ($col === $totalSwCol || $label === null || $label === '') {
                continue;
            }

            $age = $this->parseAgeLabel((string) $label);

            if ($age !== null) {
                $gesamtAgeCols[$col] = $age;
            }
        }

        $totalData = [];

        for ($r = $dataStartRow; $this->isNumericCell($rows[$r][$totalSwCol] ?? null); $r++) {
            $totalSw = (int) $rows[$r][$totalSwCol];

            foreach ($gesamtAgeCols as $col => $age) {
                $iqVal = $rows[$r][$col] ?? null;

                if ($this->isNumericCell($iqVal)) {
                    $iq = (int) $iqVal;
                    $totalData[] = [
                        'min_age' => $age['min'],
                        'max_age' => $age['max'],
                        'total_sw' => $totalSw,
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
     * Konversi label usia dari sheet ("13 tahun", "<=20 tahun", "<20", ">45
     * tahun", "<60", dst) menjadi rentang [min, max]. Pita usia berurutan
     * (13,14,15,16,17,18) sehingga tiap batas atas berarti usia setelah pita
     * eksplisit sebelumnya s/d batas tsb. Pita terakhir muncul sebagai ">45"
     * pada tabel per-subtes dan "<60" pada tabel Gesamt di kedua file sumber
     * -> keduanya dipetakan ke pita usia dewasa terakhir yang sama (46-59).
     */
    private function parseAgeLabel(string $label): ?array
    {
        $label = trim($label);

        if (preg_match('/^(\d+)(\s*tahun)?$/i', $label, $m)) {
            $age = (int) $m[1];

            return ['min' => $age, 'max' => $age];
        }

        if (preg_match('/^<=?\s*(\d+)/', $label, $m)) {
            $upper = (int) $m[1];

            return match ($upper) {
                20 => ['min' => 19, 'max' => 20],
                24 => ['min' => 21, 'max' => 24],
                28 => ['min' => 25, 'max' => 28],
                33 => ['min' => 29, 'max' => 33],
                39 => ['min' => 34, 'max' => 39],
                45 => ['min' => 40, 'max' => 45],
                60 => ['min' => 46, 'max' => 59],
                default => null,
            };
        }

        if (preg_match('/^>\s*(\d+)/', $label, $m)) {
            $lower = (int) $m[1];

            return match ($lower) {
                45 => ['min' => 46, 'max' => 59],
                default => null,
            };
        }

        return null;
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

    private function isNumericCell(mixed $value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }

    private function findRowWithCellValue(array $rows, string $value, int $fromRow, int $toRow): ?int
    {
        $toRow = min($toRow, count($rows) - 1);

        for ($r = $fromRow; $r <= $toRow; $r++) {
            foreach ($rows[$r] as $cell) {
                if (is_string($cell) && strtoupper(trim($cell)) === strtoupper($value)) {
                    return $r;
                }
            }
        }

        return null;
    }

    private function findRowContaining(array $rows, string $needle): ?int
    {
        foreach ($rows as $r => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && stripos($cell, $needle) !== false) {
                    return $r;
                }
            }
        }

        return null;
    }

    private function findColumnsWithCellValue(array $row, string $value): array
    {
        $cols = [];

        foreach ($row as $col => $cell) {
            if (is_string($cell) && strtoupper(trim($cell)) === strtoupper($value)) {
                $cols[] = $col;
            }
        }

        return $cols;
    }
}
