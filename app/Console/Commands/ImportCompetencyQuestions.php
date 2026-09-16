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
    protected $signature = 'competency:import {path? : Lokasi file soal-competency.xlsx}';

    protected $description = 'Import bank soal Tes Kompetensi (per divisi/subtes) dari file soal-competency.xlsx';

    public function handle(): int
    {
        $path = $this->argument('path') ?? base_path('soal-competency.xlsx');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $departments = config('competency.departments');

        $spreadsheet = IOFactory::load($path);

        DB::transaction(function () use ($spreadsheet, $departments) {
            foreach ($departments as $departmentKey => $department) {
                $sheet = $spreadsheet->getSheetByName($department['sheet']);

                if ($sheet === null) {
                    $this->warn("Sheet '{$department['sheet']}' tidak ditemukan, dilewati.");

                    continue;
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

    private function importSheet(string $departmentKey, Worksheet $sheet): void
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $highestRow = $sheet->getHighestDataRow();

        // Baris 1 menandai kolom awal tiap blok kompetensi (nama subtes).
        $blockStarts = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = trim((string) $this->cellValue($sheet, $col, 1));

            if ($value !== '') {
                $blockStarts[$col] = $value;
            }
        }

        $starts = array_keys($blockStarts);
        sort($starts);

        foreach ($starts as $index => $startCol) {
            $endCol = $starts[$index + 1] ?? ($highestColumnIndex + 1);
            $endCol--;

            $categoryName = $blockStarts[$startCol];

            $category = CompetencyCategory::query()->updateOrCreate(
                ['department' => $departmentKey, 'name' => $categoryName],
                ['order' => $index + 1],
            );

            $this->importBlock($category, $sheet, $startCol, $endCol, $highestRow);
        }
    }

    private function importBlock(
        CompetencyCategory $category,
        Worksheet $sheet,
        int $startCol,
        int $endCol,
        int $highestRow,
    ): void {
        // Baris 2 = header kolom pada blok ini (No, Soal/Situasi, lalu pilihan).
        $headers = [];

        for ($col = $startCol; $col <= $endCol; $col++) {
            $value = trim((string) $this->cellValue($sheet, $col, 2));

            if ($value !== '') {
                $headers[$col] = $value;
            }
        }

        $headerCols = array_keys($headers);

        // Kolom pertama = No, kolom kedua = teks soal (Soal/Situasi).
        $questionTextCol = $headerCols[1] ?? null;

        if ($questionTextCol === null) {
            return;
        }

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

        $labels = ['A', 'B', 'C', 'D', 'E'];

        for ($row = 3; $row <= $highestRow; $row++) {
            $questionNumberRaw = $this->cellValue($sheet, $startCol, $row);
            $questionText = trim((string) $this->cellValue($sheet, $questionTextCol, $row));

            if ($questionText === '' || trim((string) $questionNumberRaw) === '') {
                continue;
            }

            $question = CompetencyQuestion::query()->updateOrCreate(
                [
                    'competency_category_id' => $category->id,
                    'question_number' => (int) $questionNumberRaw,
                ],
                ['question_text' => $questionText],
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
