<?php

namespace App\Console\Commands;

use App\Models\CompetencyCategory;
use App\Models\CompetencyQuestion;
use App\Models\CompetencyQuestionOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportCompetencyQuestions extends Command
{
    protected $signature = 'competency:import {path? : Lokasi file soal-kompetensi.xlsx} {--fresh : Hapus seluruh kategori/soal/opsi lama tiap divisi sebelum impor, lalu ganti total dengan isi file}';

    protected $description = 'Import bank soal Tes Kompetensi (per divisi/subtes) dari file soal-kompetensi.xlsx';

    public function handle(): int
    {
        $path = $this->argument('path') ?? base_path('soal-kompetensi.xlsx');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $fresh = (bool) $this->option('fresh');
        $departments = config('competency.departments');

        $spreadsheet = IOFactory::load($path);

        DB::transaction(function () use ($spreadsheet, $departments, $fresh) {
            foreach ($departments as $departmentKey => $department) {
                $sheet = $spreadsheet->getSheetByName($department['sheet']);

                if ($sheet === null) {
                    $this->warn("Sheet '{$department['sheet']}' tidak ditemukan, dilewati.");

                    continue;
                }

                if ($fresh) {
                    // Replace total: hapus kategori lama divisi ini (cascade ke
                    // questions/options/test-questions/answers) sebelum diisi ulang
                    // dari file, supaya tidak ada kategori/soal basi yang tersisa.
                    CompetencyCategory::query()
                        ->where('department', $departmentKey)
                        ->delete();
                }

                $this->importSheet($departmentKey, $sheet);
            }
        });

        $this->info('Import soal kompetensi selesai.');

        return self::SUCCESS;
    }

    private function cellValue(Worksheet $sheet, int $col, int $row): mixed
    {
        return $sheet->getCell([$col, $row])->getValue();
    }

    /**
     * soal-kompetensi.xlsx menyusun tiap blok sub-kompetensi secara bertumpuk
     * (bukan bersebelahan seperti soal-competency.xlsx lama): baris judul
     * blok (hanya kolom A terisi) -> baris header "No"/"Soal"/pilihan -> N
     * baris soal -> baris kosong pemisah -> blok berikutnya.
     */
    private function importSheet(string $departmentKey, Worksheet $sheet): void
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $highestRow = $sheet->getHighestDataRow();

        $labels = ['A', 'B', 'C', 'D', 'E'];

        $category = null;
        $order = 0;
        $questionTextCol = null;
        $choiceCols = [];
        $hasExplicitPoinColumns = false;

        for ($row = 1; $row <= $highestRow; $row++) {
            $colA = trim((string) $this->cellValue($sheet, 1, $row));
            $colB = trim((string) $this->cellValue($sheet, 2, $row));

            if ($colA === '') {
                // Baris kosong pemisah antar blok.
                continue;
            }

            if ($colB === '') {
                // Baris judul blok (nama sub-kompetensi).
                $order++;
                $category = CompetencyCategory::query()->updateOrCreate(
                    ['department' => $departmentKey, 'name' => $colA],
                    ['order' => $order],
                );
                $questionTextCol = null;

                continue;
            }

            if (strtolower($colA) === 'no') {
                // Baris header kolom pada blok ini.
                $headers = [];

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $value = trim((string) $this->cellValue($sheet, $col, $row));

                    if ($value !== '') {
                        $headers[$col] = $value;
                    }
                }

                $headerCols = array_keys($headers);
                $questionTextCol = $headerCols[1] ?? null;
                $choiceCols = array_slice($headerCols, 2);

                // Format A: kolom "Pilihan X" diikuti kolom "Poin"/"Poin X" terpisah.
                // Format B: kolom pilihan tunggal berlabel A-E, poin tertanam pada teks "... (N)".
                $hasExplicitPoinColumns = false;

                foreach ($choiceCols as $col) {
                    if (str_starts_with(strtolower($headers[$col]), 'poin')) {
                        $hasExplicitPoinColumns = true;
                        break;
                    }
                }

                continue;
            }

            // Baris soal.
            if ($category === null || $questionTextCol === null) {
                continue;
            }

            $question = CompetencyQuestion::query()->updateOrCreate(
                [
                    'competency_category_id' => $category->id,
                    'question_number' => (int) $colA,
                ],
                ['question_text' => trim((string) $this->cellValue($sheet, $questionTextCol, $row))],
            );

            $options = $hasExplicitPoinColumns
                ? $this->readExplicitPoinOptions($sheet, $row, $choiceCols, $labels)
                : $this->readEmbeddedPoinOptions($sheet, $row, $choiceCols, $labels);

            foreach ($options as $label => $option) {
                CompetencyQuestionOption::query()->updateOrCreate(
                    [
                        'competency_question_id' => $question->id,
                        'label' => $label,
                    ],
                    [
                        'option_text' => $option['text'],
                        'points' => $option['points'],
                    ],
                );
            }
        }
    }

    /**
     * @param  array<int>  $choiceCols
     * @param  array<int, string>  $labels
     * @return array<string, array{text: string, points: int}>
     */
    private function readExplicitPoinOptions(Worksheet $sheet, int $row, array $choiceCols, array $labels): array
    {
        $options = [];
        $labelIndex = 0;

        for ($i = 0; $i < count($choiceCols); $i += 2) {
            $textCol = $choiceCols[$i];
            $poinCol = $choiceCols[$i + 1] ?? null;

            if ($poinCol === null || ! isset($labels[$labelIndex])) {
                break;
            }

            $text = trim((string) $this->cellValue($sheet, $textCol, $row));
            $points = (int) $this->cellValue($sheet, $poinCol, $row);

            $options[$labels[$labelIndex]] = ['text' => $text, 'points' => $points];
            $labelIndex++;
        }

        return $options;
    }

    /**
     * @param  array<int>  $choiceCols
     * @param  array<int, string>  $labels
     * @return array<string, array{text: string, points: int}>
     */
    private function readEmbeddedPoinOptions(Worksheet $sheet, int $row, array $choiceCols, array $labels): array
    {
        $options = [];

        foreach (array_values($choiceCols) as $labelIndex => $col) {
            if (! isset($labels[$labelIndex])) {
                break;
            }

            $raw = trim((string) $this->cellValue($sheet, $col, $row));

            $points = 0;
            $text = $raw;

            if (preg_match('/^(.*)\((\d+)\)\s*$/', $raw, $matches) === 1) {
                $text = trim($matches[1]);
                $points = (int) $matches[2];
            }

            $options[$labels[$labelIndex]] = ['text' => $text, 'points' => $points];
        }

        return $options;
    }
}
