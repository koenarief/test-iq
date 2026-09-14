<?php

namespace App\Console\Commands;

use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Support\Ist\IstAnswerType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * One-off content operation: replaces the active GE/RA/ZR/ME question bank
 * with the content of an xlsx workbook (GE, RA, ZR, ME sheets), deactivating
 * (not deleting) every question it supersedes. FA and WU are intentionally
 * left untouched: their sheets only carry an answer-key list plus a single
 * page-scan image, with no per-question prompt or image to build a real
 * question from.
 *
 * GE is rescaled from the file's 0-4 answer values to a 0-2 weighted scale
 * (16 questions x max 2 = 32) rather than the app's usual 0-3, because the
 * IST age norm tables (ist_norm_subtests) only cover GE raw scores 0-32; a
 * 0-3 scale over 16 questions (max 48) would push high scorers outside the
 * norm table and blank out their IQ result entirely.
 */
final class ImportIstXlsxQuestionBank extends Command
{
    protected $signature = 'ist:import-xlsx-question-bank
                            {path=soal-ist.xlsx : Path to the xlsx question bank, relative to the project root}
                            {--confirm : Perform the import instead of a dry-run}
                            {--force-active-tests : Proceed even if draft/in-progress IST tests exist (only safe when none are a live participant right now)}';

    protected $description = 'Replace the active GE/RA/ZR/ME IST question bank with the content of an xlsx file, deactivating the questions it supersedes';

    private const OPTION_KEYS = ['A', 'B', 'C', 'D', 'E'];

    private const DIFFICULTY_CYCLE = ['easy', 'medium', 'hard'];

    private const ME_GROUP_KEYS = [
        'Bunga' => 'A',
        'Perkakas' => 'B',
        'Burung' => 'C',
        'Kesenian' => 'D',
        'Binatang' => 'E',
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $dataset = $this->buildDataset($path);

        $mode = $this->option('confirm') ? 'ACTUAL' : 'DRY-RUN';
        $this->line("IST xlsx question bank import ({$mode}) from {$path}:");

        foreach ($dataset as $code => $payload) {
            $this->line(sprintf(
                '  %s: %d active now -> %d active after import (%d new questions, max_score=%s)',
                $code,
                $payload['previous_active_count'],
                count($payload['questions']),
                count($payload['questions']),
                $payload['max_score'],
            ));
        }

        if (! $this->option('confirm')) {
            $this->warn('Dry-run only. Use --confirm to apply changes.');

            return self::SUCCESS;
        }

        if (! $this->option('force-active-tests')) {
            $this->assertNoActiveTests();
        }

        DB::transaction(function () use ($dataset): void {
            if (! $this->option('force-active-tests')) {
                $this->assertNoActiveTests(true);
            }

            foreach ($dataset as $code => $payload) {
                $this->importSubtest($code, $payload);
            }
        }, 3);

        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function importSubtest(string $code, array $payload): void
    {
        /** @var IstSubtest $subtest */
        $subtest = IstSubtest::query()->where('code', $code)->lockForUpdate()->firstOrFail();

        IstQuestion::query()
            ->where('ist_subtest_id', $subtest->id)
            ->where('kind', IstQuestion::KIND_SCORED)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $nextNumber = 1 + (int) (IstQuestion::withTrashed()
            ->where('ist_subtest_id', $subtest->id)
            ->where('kind', IstQuestion::KIND_SCORED)
            ->max('question_number') ?? 0);

        $version = 1 + (int) (IstQuestion::withTrashed()
            ->where('ist_subtest_id', $subtest->id)
            ->max('version') ?? 0);

        foreach ($payload['questions'] as $index => $questionData) {
            $question = IstQuestion::create([
                'ist_subtest_id' => $subtest->id,
                'question_number' => $nextNumber + $index,
                'display_order' => $index + 1,
                'kind' => IstQuestion::KIND_SCORED,
                'answer_type' => $payload['answer_type'],
                'prompt' => $questionData['prompt'],
                'numeric_answer_key' => $questionData['numeric_answer_key'] ?? null,
                'max_score' => $payload['max_score'],
                'difficulty' => self::DIFFICULTY_CYCLE[$index % 3],
                'version' => $version,
                'is_active' => true,
            ]);

            foreach ($questionData['options'] ?? [] as $optionIndex => $optionData) {
                IstQuestionOption::create([
                    'ist_question_id' => $question->id,
                    'option_key' => self::OPTION_KEYS[$optionIndex],
                    'option_text' => $optionData['text'],
                    'display_order' => $optionIndex + 1,
                    'is_correct' => $optionData['is_correct'],
                    'score_value' => $optionData['score'],
                    'is_active' => true,
                ]);
            }
        }

        if ($code === 'ME') {
            $subtest->memorization_content = json_encode(
                ['groups' => $payload['memorization_groups']],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $subtest->question_count = IstQuestion::query()
            ->where('ist_subtest_id', $subtest->id)
            ->where('kind', IstQuestion::KIND_SCORED)
            ->where('is_active', true)
            ->count();
        $subtest->save();
    }

    private function assertNoActiveTests(bool $lock = false): void
    {
        $query = IstTest::query()->whereIn('status', [IstTest::STATUS_DRAFT, IstTest::STATUS_IN_PROGRESS]);

        if ($lock) {
            $query->lockForUpdate();
        }

        if ($query->exists()) {
            throw InvalidIstQuestionDatasetException::because('import ditolak karena terdapat test IST draft/in_progress');
        }
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    private function buildDataset(string $path): array
    {
        $spreadsheet = IOFactory::load($path);

        return [
            'GE' => $this->buildGe($this->requireSheet($spreadsheet, 'GE')),
            'RA' => $this->buildRa($this->requireSheet($spreadsheet, 'RA')),
            'ZR' => $this->buildZr($this->requireSheet($spreadsheet, 'ZR')),
            'ME' => $this->buildMe($this->requireSheet($spreadsheet, 'ME')),
        ];
    }

    private function requireSheet($spreadsheet, string $name): Worksheet
    {
        $sheet = $spreadsheet->getSheetByName($name);

        if (! $sheet) {
            throw new RuntimeException("Workbook is missing the '{$name}' sheet.");
        }

        return $sheet;
    }

    /**
     * GE columns: No | Soal | Nilai 4 | Nilai 3 | Nilai 2 | Nilai 1 | Nilai 0.
     * Rescaled to a 0-2 scale: Nilai4 -> 2 (correct), Nilai3 -> 1 (partial),
     * Nilai2/Nilai1/Nilai0 -> 0.
     */
    private function buildGe(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);
        $scoresByColumn = ['C' => 2, 'D' => 1, 'E' => 0, 'F' => 0, 'G' => 0];
        $questions = [];

        for ($row = 3; $row <= 18; $row++) {
            $pair = trim((string) ($rows[$row]['B'] ?? ''));

            if ($pair === '') {
                continue;
            }

            $words = preg_split('/\s*[\x{2012}-\x{2015}-]\s*/u', $pair, 2);

            if (! is_array($words) || count($words) !== 2) {
                throw new RuntimeException("GE row {$row}: could not split word pair '{$pair}'.");
            }

            [$wordOne, $wordTwo] = array_map('trim', $words);

            $options = [];

            foreach ($scoresByColumn as $column => $score) {
                $text = trim((string) ($rows[$row][$column] ?? ''));

                if ($text === '') {
                    throw new RuntimeException("GE row {$row}: answer column {$column} is empty.");
                }

                $options[] = ['text' => $text, 'score' => $score, 'is_correct' => $score === 2];
            }

            $questions[] = [
                'prompt' => "Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: {$wordOne} — {$wordTwo}.",
                'options' => $options,
            ];
        }

        $this->assertCount($questions, 16, 'GE');

        return [
            'answer_type' => IstAnswerType::SINGLE_CHOICE_WEIGHTED,
            'max_score' => 2,
            'questions' => $questions,
            'previous_active_count' => $this->activeCount('GE'),
        ];
    }

    /**
     * RA columns: No | Soal | Jawaban. Answers carry units/currency text
     * (e.g. "35 rupiah", "Rp50", "5%"); only the numeric part is kept.
     */
    private function buildRa(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);
        $questions = [];

        for ($row = 3; $row <= 22; $row++) {
            $prompt = trim((string) ($rows[$row]['B'] ?? ''));
            $answerRaw = trim((string) ($rows[$row]['C'] ?? ''));

            if ($prompt === '') {
                continue;
            }

            if (! preg_match('/\d+/', $answerRaw, $matches)) {
                throw new RuntimeException("RA row {$row}: could not extract a number from answer '{$answerRaw}'.");
            }

            $questions[] = [
                'prompt' => $prompt,
                'numeric_answer_key' => $matches[0],
                'options' => [],
            ];
        }

        $this->assertCount($questions, 20, 'RA');

        return [
            'answer_type' => IstAnswerType::NUMERIC,
            'max_score' => 1,
            'questions' => $questions,
            'previous_active_count' => $this->activeCount('RA'),
        ];
    }

    /**
     * ZR columns: No. | Soal | Jawaban. Soal is a number sequence ending in
     * "?"; Jawaban is already a plain integer.
     */
    private function buildZr(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);
        $questions = [];

        for ($row = 3; $row <= 22; $row++) {
            $sequence = trim((string) ($rows[$row]['B'] ?? ''));
            $answerRaw = trim((string) ($rows[$row]['C'] ?? ''));

            if ($sequence === '') {
                continue;
            }

            if (! preg_match('/^-?\d+$/', $answerRaw)) {
                throw new RuntimeException("ZR row {$row}: answer '{$answerRaw}' is not a plain integer.");
            }

            $questions[] = [
                'prompt' => "Tentukan angka berikutnya: {$sequence}",
                'numeric_answer_key' => $answerRaw,
                'options' => [],
            ];
        }

        $this->assertCount($questions, 20, 'ZR');

        return [
            'answer_type' => IstAnswerType::NUMERIC,
            'max_score' => 1,
            'questions' => $questions,
            'previous_active_count' => $this->activeCount('ZR'),
        ];
    }

    /**
     * ME columns: No. | Soal | Pilihan Jawaban | Jawaban | (blank) | Kategori
     * | Kata-kata. Rows 3-7 additionally carry the 5-category, 5-word memory
     * bank (columns F/G) that replaces the subtest's memorization_content.
     * The options text/order for every question is always the same 5
     * categories in a-e order, so option_key A..E is derived positionally.
     */
    private function buildMe(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);

        $groupsByKey = [];

        for ($row = 3; $row <= 7; $row++) {
            $category = trim((string) ($rows[$row]['F'] ?? ''));
            $wordsRaw = trim((string) ($rows[$row]['G'] ?? ''));

            if ($category === '' || $wordsRaw === '') {
                continue;
            }

            if (! isset(self::ME_GROUP_KEYS[$category])) {
                throw new RuntimeException("ME row {$row}: unexpected category '{$category}'.");
            }

            $words = array_map('trim', explode(',', $wordsRaw));

            if (count($words) !== 5) {
                throw new RuntimeException("ME category '{$category}' does not have exactly 5 words.");
            }

            $groupsByKey[self::ME_GROUP_KEYS[$category]] = ['name' => $category, 'words' => $words];
        }

        if (count($groupsByKey) !== 5) {
            throw new RuntimeException('ME word bank does not have exactly 5 categories.');
        }

        ksort($groupsByKey);

        $memorizationGroups = [];
        $categoryNameByKey = [];
        $displayOrder = 1;

        foreach ($groupsByKey as $key => $group) {
            $memorizationGroups[] = [
                'key' => $key,
                'name' => $group['name'],
                'words' => $group['words'],
                'display_order' => $displayOrder++,
            ];
            $categoryNameByKey[$key] = $group['name'];
        }

        $questions = [];

        for ($row = 3; $row <= 22; $row++) {
            $prompt = trim((string) ($rows[$row]['B'] ?? ''));
            $answerRaw = trim((string) ($rows[$row]['D'] ?? ''));

            if ($prompt === '') {
                continue;
            }

            if (! preg_match('/^([a-eA-E])\./', $answerRaw, $matches)) {
                throw new RuntimeException("ME row {$row}: could not parse answer letter from '{$answerRaw}'.");
            }

            $correctKey = self::OPTION_KEYS[ord(strtolower($matches[1])) - ord('a')];

            $options = [];

            foreach (self::OPTION_KEYS as $key) {
                $options[] = [
                    'text' => $categoryNameByKey[$key],
                    'score' => $key === $correctKey ? 1 : 0,
                    'is_correct' => $key === $correctKey,
                ];
            }

            $questions[] = [
                'prompt' => $prompt,
                'options' => $options,
            ];
        }

        $this->assertCount($questions, 20, 'ME');

        return [
            'answer_type' => IstAnswerType::SINGLE_CHOICE,
            'max_score' => 1,
            'questions' => $questions,
            'memorization_groups' => $memorizationGroups,
            'previous_active_count' => $this->activeCount('ME'),
        ];
    }

    private function assertCount(array $questions, int $expected, string $code): void
    {
        if (count($questions) !== $expected) {
            throw new RuntimeException(
                "{$code} sheet yielded ".count($questions)." questions, expected {$expected}."
            );
        }
    }

    private function activeCount(string $code): int
    {
        return IstQuestion::query()
            ->whereHas('subtest', fn ($query) => $query->where('code', $code))
            ->where('kind', IstQuestion::KIND_SCORED)
            ->where('is_active', true)
            ->count();
    }
}
