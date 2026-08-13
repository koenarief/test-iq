<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expectedDatabase = trim((string) getenv('IST_SE_RUNTIME_DATABASE'));
$apply = getenv('IST_SE_RUNTIME_APPLY') === '1';

if ($expectedDatabase === '') {
    throw new RuntimeException('IST_SE_RUNTIME_DATABASE wajib diisi dengan nama database target yang eksplisit.');
}

$activeDatabase = (string) (DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? '');

if ($activeDatabase === '' || ! hash_equals($expectedDatabase, $activeDatabase)) {
    throw new RuntimeException('Database aktif tidak cocok dengan IST_SE_RUNTIME_DATABASE; sinkronisasi dihentikan.');
}

$dataset = json_decode(
    (string) file_get_contents(database_path('data/ist-final-staging/se.json')),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

if (($dataset['subtest_code'] ?? null) !== 'SE'
    || ($dataset['review_status'] ?? null) !== 'human_review_passed'
    || ($dataset['active'] ?? null) !== false
    || ! is_string($dataset['instruction_content'] ?? null)
    || trim($dataset['instruction_content']) === '') {
    throw new RuntimeException('Canonical staging SE tidak valid atau belum human-reviewed dan inactive.');
}

$canonicalQuestions = collect($dataset['questions'] ?? [])->keyBy(
    static fn (array $question): string => $question['kind'].'|'.$question['question_number'],
);

if ($canonicalQuestions->count() !== 13
    || $canonicalQuestions->where('kind', 'example')->count() !== 1
    || $canonicalQuestions->where('kind', 'scored')->count() !== 12) {
    throw new RuntimeException('Canonical staging SE harus memuat satu example dan dua belas scored questions.');
}

$synchronize = static function (bool $write) use ($canonicalQuestions, $dataset): array {
    $subtest = DB::table('ist_subtests')
        ->where('code', 'SE')
        ->where('is_active', true)
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->first();

    if (! $subtest || (int) $subtest->question_count !== 12 || (int) $subtest->duration_seconds !== 240) {
        throw new RuntimeException('Master subtest SE aktif tidak sesuai kontrak 12 soal dan 240 detik.');
    }

    $subtestUpdates = $subtest->instruction_content === $dataset['instruction_content'] ? 0 : 1;

    if ($write && $subtestUpdates === 1) {
        DB::table('ist_subtests')
            ->where('id', $subtest->id)
            ->update([
                'instruction_content' => $dataset['instruction_content'],
                'updated_at' => now(),
            ]);
    }

    $questions = DB::table('ist_questions')
        ->where('ist_subtest_id', $subtest->id)
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->get();

    if ($questions->count() !== 13) {
        throw new RuntimeException('Master SE aktif harus memuat tepat tiga belas record.');
    }

    $questionUpdates = 0;
    $optionUpdates = 0;

    foreach ($questions as $question) {
        $key = $question->kind.'|'.$question->question_number;
        $canonical = $canonicalQuestions->get($key);

        if (! is_array($canonical)
            || $question->answer_type !== 'single_choice'
            || (int) $question->version !== (int) $canonical['source_version']) {
            throw new RuntimeException("Identitas master SE {$key} berbeda dari canonical.");
        }

        $expectedDifficulty = $question->kind === 'example'
            ? $question->difficulty
            : $canonical['difficulty_target'];
        $expectedExplanation = $question->kind === 'example'
            ? $canonical['explanation']
            : null;
        $questionChanged = $question->prompt !== $canonical['prompt']
            || $question->example_explanation !== $expectedExplanation
            || $question->difficulty !== $expectedDifficulty
            || (float) $question->max_score !== (float) $canonical['scoring']['max_score']
            || (int) $question->display_order !== (int) $canonical['display_order'];

        if ($questionChanged) {
            $questionUpdates++;

            if ($write) {
                DB::table('ist_questions')
                    ->where('id', $question->id)
                    ->update([
                        'prompt' => $canonical['prompt'],
                        'example_explanation' => $expectedExplanation,
                        'difficulty' => $expectedDifficulty,
                        'max_score' => $canonical['scoring']['max_score'],
                        'display_order' => $canonical['display_order'],
                        'updated_at' => now(),
                    ]);
            }
        }

        $options = DB::table('ist_question_options')
            ->where('ist_question_id', $question->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when($write, static fn ($query) => $query->lockForUpdate())
            ->get();

        if ($options->count() !== 5) {
            throw new RuntimeException("Master SE {$key} tidak mempunyai tepat lima opsi aktif.");
        }

        foreach ($options as $option) {
            $canonicalOption = collect($canonical['options'])->firstWhere('key', $option->option_key);

            if (! is_array($canonicalOption)) {
                throw new RuntimeException("Opsi SE {$key}/{$option->option_key} tidak tersedia pada canonical.");
            }

            $optionChanged = $option->option_text !== $canonicalOption['text']
                || (bool) $option->is_correct !== (bool) $canonicalOption['correct']
                || (float) $option->score_value !== (float) $canonicalOption['score']
                || (int) $option->display_order !== (int) $canonicalOption['display_order'];

            if ($optionChanged) {
                $optionUpdates++;

                if ($write) {
                    DB::table('ist_question_options')
                        ->where('id', $option->id)
                        ->update([
                            'option_text' => $canonicalOption['text'],
                            'is_correct' => $canonicalOption['correct'],
                            'score_value' => $canonicalOption['score'],
                            'display_order' => $canonicalOption['display_order'],
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    return [
        'subtest_updates' => $subtestUpdates,
        'question_updates' => $questionUpdates,
        'option_updates' => $optionUpdates,
    ];
};

if ($apply) {
    $summary = DB::transaction(static fn (): array => $synchronize(true), 3);
    $postflight = $synchronize(false);

    if (array_sum($postflight) !== 0) {
        throw new RuntimeException('Postflight SE masih menemukan drift setelah sinkronisasi.');
    }
} else {
    $summary = $synchronize(false);
}

$legacySessions = DB::table('ist_test_questions as tq')
    ->join('ist_test_subtests as ts', 'ts.id', '=', 'tq.ist_test_subtest_id')
    ->join('ist_subtests as s', 's.id', '=', 'ts.ist_subtest_id')
    ->join('ist_tests as t', 't.id', '=', 'ts.ist_test_id')
    ->where('s.code', 'SE')
    ->where('t.status', 'in_progress')
    ->where('tq.display_order', 1)
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(tq.question_snapshot, '$.prompt')) <> ?", [
        $canonicalQuestions->get('scored|1')['prompt'],
    ])
    ->distinct()
    ->count('t.id');

echo 'ENV='.app()->environment().PHP_EOL;
echo 'DATABASE='.$activeDatabase.PHP_EOL;
echo 'MODE='.($apply ? 'APPLY' : 'DRY_RUN').PHP_EOL;
echo 'SUBTEST_UPDATES='.$summary['subtest_updates'].PHP_EOL;
echo 'QUESTION_UPDATES='.$summary['question_updates'].PHP_EOL;
echo 'OPTION_UPDATES='.$summary['option_updates'].PHP_EOL;
echo 'LEGACY_IN_PROGRESS_SESSIONS='.$legacySessions.PHP_EOL;
echo 'STATUS=PASS'.PHP_EOL;
