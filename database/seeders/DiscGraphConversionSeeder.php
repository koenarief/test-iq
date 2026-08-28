<?php

namespace Database\Seeders;

use App\Models\DiscGraphConversion;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DiscGraphConversionSeeder extends Seeder
{
    private const DIMENSIONS = ['D', 'I', 'S', 'C'];

    /**
     * Rentang nilai grafik asli pada storage/app/norma-disc.xlsx (dikonfirmasi
     * dengan memindai seluruh sel tabel norma: nilai minimum -8, maksimum 8).
     * Dipakai sebagai acuan tetap untuk rescale linear ke skala 0-100 yang
     * dipakai kolom graph_score, chart hasil, dan aturan gabung tipe
     * primary+secondary (selisih <=5) di seluruh aplikasi.
     */
    private const RAW_MIN = -8;

    private const RAW_MAX = 8;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DiscGraphConversion::truncate();

        $filePath = storage_path('app/norma-disc.xlsx');

        if (file_exists($filePath)) {
            $this->seedFromExcel($filePath);

            return;
        }

        $this->seedFallbackData();
    }

    /**
     * Parsing dinamis dari sheet NORMA-DISC. Layout file terdiri dari 3 tabel
     * konversi raw score -> nilai grafik per dimensi D/I/S/C:
     *
     *  - Dua tabel berdampingan pada baris atas (raw score 0-20, satu kolom
     *    raw score dipakai bersama): blok kolom pertama = Graph I (Most),
     *    blok kolom kedua = Graph II (Least).
     *  - Satu tabel di bawahnya (raw score -22..22) = Graph III (Change,
     *    Most - Least), berdiri sendiri dengan kolom raw score sendiri.
     *
     * Tiap tabel ditandai baris header berisi sel berurutan "D","I","S","C".
     * Kolom tepat di sebelah kiri kolom "D" adalah kolom raw score, dan data
     * dimulai satu baris setelah header, berlanjut selama kolom raw score
     * tetap numerik.
     */
    private function seedFromExcel(string $filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('NORMA-DISC');
        $rows = $sheet->toArray();

        $topHeader = $this->findHeaderBlocks($rows, 0);

        if ($topHeader['row'] === null || count($topHeader['cols']) < 2) {
            $this->seedFallbackData();

            return;
        }

        [$mostCol, $leastCol] = $topHeader['cols'];

        // Kedua blok (Most & Least) berbagi satu kolom raw score yang sama,
        // terletak tepat di sebelah kiri blok pertama.
        $topRawScoreCol = $mostCol - 1;

        $this->seedTable($rows, $topHeader['row'], $topRawScoreCol, $mostCol, 'most');
        $this->seedTable($rows, $topHeader['row'], $topRawScoreCol, $leastCol, 'least');

        $changeHeader = $this->findHeaderBlocks($rows, $topHeader['row'] + 1);

        if ($changeHeader['row'] !== null && count($changeHeader['cols']) >= 1) {
            $changeCol = $changeHeader['cols'][0];

            $this->seedTable($rows, $changeHeader['row'], $changeCol - 1, $changeCol, 'change');
        }
    }

    private function seedTable(array $rows, int $headerRow, int $rawScoreCol, int $startCol, string $graphType): void
    {
        $dataStartRow = $headerRow + 1;

        $data = [];

        for ($r = $dataStartRow; $this->isNumericCell($rows[$r][$rawScoreCol] ?? null); $r++) {
            $rawScore = (int) $rows[$r][$rawScoreCol];

            foreach (self::DIMENSIONS as $idx => $dimension) {
                $value = $rows[$r][$startCol + $idx] ?? null;

                if (! $this->isNumericCell($value)) {
                    continue;
                }

                $data[] = [
                    'graph_type' => $graphType,
                    'dimension' => $dimension,
                    'raw_score' => $rawScore,
                    'graph_score' => $this->rescaleToGraphScore((float) $value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($data, 100) as $chunk) {
            DiscGraphConversion::insert($chunk);
        }
    }

    /**
     * Rescale nilai grafik asli (skala -8..8 pada file sumber) ke skala
     * 0-100 secara linear, supaya kompatibel dengan kolom graph_score
     * (unsignedTinyInteger), chart hasil (Y-axis 0-100), dan ambang batas
     * gabung tipe primary+secondary yang sudah ada di aplikasi.
     */
    private function rescaleToGraphScore(float $rawValue): int
    {
        $clamped = max(self::RAW_MIN, min(self::RAW_MAX, $rawValue));

        $percentage = (($clamped - self::RAW_MIN) / (self::RAW_MAX - self::RAW_MIN)) * 100;

        return (int) round($percentage);
    }

    /**
     * Cari baris berikutnya (mulai dari $fromRow) yang memuat satu atau
     * lebih blok header "D","I","S","C" berurutan, lalu kembalikan indeks
     * baris tsb beserta kolom awal ("D") tiap blok yang ditemukan.
     */
    private function findHeaderBlocks(array $rows, int $fromRow): array
    {
        for ($r = $fromRow; $r < count($rows); $r++) {
            $row = $rows[$r];
            $cols = [];

            foreach ($row as $c => $value) {
                if ($this->cellEquals($value, 'D')
                    && $this->cellEquals($row[$c + 1] ?? null, 'I')
                    && $this->cellEquals($row[$c + 2] ?? null, 'S')
                    && $this->cellEquals($row[$c + 3] ?? null, 'C')) {
                    $cols[] = $c;
                }
            }

            if (! empty($cols)) {
                return ['row' => $r, 'cols' => $cols];
            }
        }

        return ['row' => null, 'cols' => []];
    }

    private function cellEquals(mixed $value, string $expected): bool
    {
        return is_string($value) && strtoupper(trim($value)) === $expected;
    }

    private function isNumericCell(mixed $value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }

    /**
     * Fallback linear jika file Excel belum tersedia di storage, supaya
     * fitur tes DISC tetap berjalan (skor tidak sepresisi data norma asli).
     */
    private function seedFallbackData(): void
    {
        $data = [];

        foreach (self::DIMENSIONS as $dimension) {
            for ($raw = 0; $raw <= 20; $raw++) {
                $graph = (int) round(($raw / 20) * 100);

                $data[] = [
                    'graph_type' => 'most',
                    'dimension' => $dimension,
                    'raw_score' => $raw,
                    'graph_score' => $graph,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $data[] = [
                    'graph_type' => 'least',
                    'dimension' => $dimension,
                    'raw_score' => $raw,
                    'graph_score' => $graph,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            for ($change = -24; $change <= 24; $change++) {
                $data[] = [
                    'graph_type' => 'change',
                    'dimension' => $dimension,
                    'raw_score' => $change,
                    'graph_score' => (int) round((($change + 24) / 48) * 100),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($data, 100) as $chunk) {
            DiscGraphConversion::insert($chunk);
        }
    }
}
