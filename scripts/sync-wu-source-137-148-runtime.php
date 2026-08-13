<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expectedDatabase = trim((string) getenv('IST_WU_RUNTIME_DATABASE'));
$apply = getenv('IST_WU_RUNTIME_APPLY') === '1';
$connection = (string) config('database.default');
$configuredDatabase = (string) config("database.connections.{$connection}.database");

if ($expectedDatabase === '') {
    throw new RuntimeException('IST_WU_RUNTIME_DATABASE wajib diisi dengan nama database target yang eksplisit.');
}

if (! app()->environment('local')
    || $connection !== 'mysql'
    || ! hash_equals($expectedDatabase, $configuredDatabase)) {
    throw new RuntimeException('Environment, connection, atau configured database tidak memenuhi guard runtime WU.');
}

$activeDatabase = (string) (DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? '');

if ($activeDatabase === '' || ! hash_equals($expectedDatabase, $activeDatabase)) {
    throw new RuntimeException('Database aktif tidak cocok dengan IST_WU_RUNTIME_DATABASE; sinkronisasi dihentikan.');
}

$dataset = json_decode(
    (string) file_get_contents(database_path('data/ist-final-staging/wu.json')),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

if (($dataset['subtest_code'] ?? null) !== 'WU'
    || ($dataset['review_status'] ?? null) !== 'human_review_passed'
    || ($dataset['active'] ?? null) !== false
    || ! is_string($dataset['instruction_content'] ?? null)
    || trim($dataset['instruction_content']) === '') {
    throw new RuntimeException('Canonical staging WU tidak valid atau belum human-reviewed dan inactive.');
}

$canonicalQuestions = collect($dataset['questions'] ?? [])->keyBy(
    static fn (array $question): string => $question['kind'].'|'.$question['question_number'],
);

if ($canonicalQuestions->count() !== 13
    || $canonicalQuestions->where('kind', 'example')->count() !== 1
    || $canonicalQuestions->where('kind', 'scored')->count() !== 12) {
    throw new RuntimeException('Canonical staging WU harus memuat satu example dan dua belas scored questions.');
}

$expectedKeys = ['A', 'C', 'D', 'E', 'A', 'C', 'D', 'C', 'E', 'A', 'B', 'D'];
$difficultyCounts = ['easy' => 0, 'medium' => 0, 'hard' => 0];
$difficultyWeights = ['easy' => 1, 'medium' => 2, 'hard' => 3];
$weightedMaximum = 0;

foreach ($canonicalQuestions as $canonical) {
    $options = $canonical['options'] ?? [];
    $correctOptions = array_values(array_filter(
        $options,
        static fn (array $option): bool => ($option['correct'] ?? false) === true,
    ));

    if (($canonical['answer_type'] ?? null) !== 'image_choice'
        || count($options) !== 5
        || array_column($options, 'key') !== ['A', 'B', 'C', 'D', 'E']
        || count($correctOptions) !== 1) {
        throw new RuntimeException('Canonical staging WU mempunyai kontrak opsi atau answer type yang tidak valid.');
    }

    foreach ($options as $option) {
        $expectedScore = ($option['correct'] ?? false) === true ? 1 : 0;

        if (($option['score'] ?? null) !== $expectedScore) {
            throw new RuntimeException('Canonical staging WU mempunyai score binary yang tidak konsisten.');
        }
    }

    if (($canonical['kind'] ?? null) === 'scored') {
        $difficulty = $canonical['difficulty_target'] ?? null;

        if (! is_string($difficulty) || ! array_key_exists($difficulty, $difficultyCounts)) {
            throw new RuntimeException('Canonical staging WU mempunyai difficulty scored yang tidak valid.');
        }

        $index = (int) $canonical['display_order'] - 1;

        if (($correctOptions[0]['key'] ?? null) !== ($expectedKeys[$index] ?? null)) {
            throw new RuntimeException('Canonical staging WU mempunyai key yang berbeda dari keputusan final sumber 137–148.');
        }

        $difficultyCounts[$difficulty]++;
        $weightedMaximum += $difficultyWeights[$difficulty];
    }
}

if ($difficultyCounts !== ['easy' => 4, 'medium' => 5, 'hard' => 3]
    || $weightedMaximum !== 23) {
    throw new RuntimeException('Difficulty atau weighted maximum canonical WU tidak sesuai kontrak 4/5/3 dan 23.');
}

$readMaster = static function (bool $lock): array {
    $subtest = DB::table('ist_subtests')
        ->where('code', 'WU')
        ->where('is_active', true)
        ->when($lock, static fn ($query) => $query->lockForUpdate())
        ->first();

    if (! $subtest || (int) $subtest->question_count !== 12 || (int) $subtest->duration_seconds !== 360) {
        throw new RuntimeException('Master subtest WU aktif tidak sesuai kontrak 12 soal dan 360 detik.');
    }

    $questions = DB::table('ist_questions')
        ->where('ist_subtest_id', $subtest->id)
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->orderBy('kind')
        ->orderBy('question_number')
        ->when($lock, static fn ($query) => $query->lockForUpdate())
        ->get();

    if ($questions->count() !== 13) {
        throw new RuntimeException('Master WU aktif harus memuat tepat tiga belas record.');
    }

    $questionIds = $questions->pluck('id');
    $options = DB::table('ist_question_options')
        ->whereIn('ist_question_id', $questionIds)
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->orderBy('ist_question_id')
        ->orderBy('display_order')
        ->when($lock, static fn ($query) => $query->lockForUpdate())
        ->get();

    if ($options->count() !== 65) {
        throw new RuntimeException('Master WU aktif harus memuat tepat enam puluh lima opsi.');
    }

    return ['subtest' => $subtest, 'questions' => $questions, 'options' => $options];
};

$backupPath = database_path('data/backup-ist-wu/wu-before-source-137-148-20260813/runtime-master-before-sync.json');

$writeBackup = static function (array $master) use ($backupPath, $activeDatabase): void {
    if (is_file($backupPath)) {
        throw new RuntimeException('Backup runtime master WU sudah ada; apply dibatalkan agar backup tidak tertimpa.');
    }

    $payload = [
        'database' => $activeDatabase,
        'created_at' => now()->toIso8601String(),
        'scope' => 'WU active master only; no session snapshots',
        'subtest' => (array) $master['subtest'],
        'questions' => array_map(static fn ($record): array => (array) $record, $master['questions']->all()),
        'options' => array_map(static fn ($record): array => (array) $record, $master['options']->all()),
    ];
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;

    if (file_put_contents($backupPath, $encoded, LOCK_EX) === false) {
        throw new RuntimeException('Backup runtime master WU gagal ditulis.');
    }
};

$synchronize = static function (bool $write) use ($canonicalQuestions, $dataset, $readMaster): array {
    $master = $readMaster($write);
    $subtest = $master['subtest'];
    $questions = $master['questions'];
    $optionsByQuestion = $master['options']->groupBy('ist_question_id');
    $subtestUpdates = $subtest->instruction_content === $dataset['instruction_content'] ? 0 : 1;

    if ($write && $subtestUpdates === 1) {
        DB::table('ist_subtests')
            ->where('id', $subtest->id)
            ->update([
                'instruction_content' => $dataset['instruction_content'],
                'updated_at' => now(),
            ]);
    }

    $questionUpdates = 0;
    $optionUpdates = 0;

    foreach ($questions as $question) {
        $key = $question->kind.'|'.$question->question_number;
        $canonical = $canonicalQuestions->get($key);

        if (! is_array($canonical)
            || $question->answer_type !== 'image_choice'
            || (int) $question->version !== (int) $canonical['source_version']) {
            throw new RuntimeException("Identitas master WU {$key} berbeda dari canonical.");
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

        $options = $optionsByQuestion->get($question->id, collect());

        if ($options->count() !== 5) {
            throw new RuntimeException("Master WU {$key} tidak mempunyai tepat lima opsi aktif.");
        }

        foreach ($options as $option) {
            $canonicalOption = collect($canonical['options'])->firstWhere('key', $option->option_key);

            if (! is_array($canonicalOption)) {
                throw new RuntimeException("Opsi WU {$key}/{$option->option_key} tidak tersedia pada canonical.");
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
    $masterBefore = $readMaster(false);
    $writeBackup($masterBefore);
    $summary = DB::transaction(static fn (): array => $synchronize(true), 3);
    $postflight = $synchronize(false);

    if (array_sum($postflight) !== 0) {
        throw new RuntimeException('Postflight WU masih menemukan drift setelah sinkronisasi.');
    }
} else {
    $summary = $synchronize(false);
}

$legacySessions = DB::table('ist_test_questions as tq')
    ->join('ist_test_subtests as ts', 'ts.id', '=', 'tq.ist_test_subtest_id')
    ->join('ist_subtests as s', 's.id', '=', 'ts.ist_subtest_id')
    ->join('ist_tests as t', 't.id', '=', 'ts.ist_test_id')
    ->where('s.code', 'WU')
    ->whereIn('t.status', ['draft', 'in_progress'])
    ->where('tq.display_order', 1)
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(tq.answer_key_snapshot, '$.correct_option_key')) <> ?", [
        $canonicalQuestions->get('scored|1')['options'][0]['key'],
    ])
    ->distinct()
    ->count('t.id');

echo 'ENV='.app()->environment().PHP_EOL;
echo 'CONNECTION='.$connection.PHP_EOL;
echo 'CONFIGURED_DATABASE='.$configuredDatabase.PHP_EOL;
echo 'ACTIVE_DATABASE='.$activeDatabase.PHP_EOL;
echo 'MODE='.($apply ? 'APPLY' : 'DRY_RUN').PHP_EOL;
echo 'SUBTEST_UPDATES='.$summary['subtest_updates'].PHP_EOL;
echo 'QUESTION_UPDATES='.$summary['question_updates'].PHP_EOL;
echo 'OPTION_UPDATES='.$summary['option_updates'].PHP_EOL;
echo 'KEYS='.implode(',', $expectedKeys).PHP_EOL;
echo 'DIFFICULTY=4/5/3'.PHP_EOL;
echo 'WEIGHTED_MAXIMUM='.$weightedMaximum.PHP_EOL;
echo 'BACKUP='.($apply ? $backupPath : 'not-created-in-dry-run').PHP_EOL;
echo 'LEGACY_DRAFT_OR_IN_PROGRESS_SESSIONS='.$legacySessions.PHP_EOL;
echo 'STATUS=PASS'.PHP_EOL;
