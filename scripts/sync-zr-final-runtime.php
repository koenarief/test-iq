<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expectedDatabase = trim((string) getenv('IST_ZR_RUNTIME_DATABASE'));
$apply = getenv('IST_ZR_RUNTIME_APPLY') === '1';
$connection = (string) config('database.default');
$configuredDatabase = (string) config("database.connections.{$connection}.database");

if ($expectedDatabase === '') {
    throw new RuntimeException('IST_ZR_RUNTIME_DATABASE wajib diisi dengan nama database target yang eksplisit.');
}

if (! app()->environment('local')
    || $connection !== 'mysql'
    || ! hash_equals($expectedDatabase, $configuredDatabase)) {
    throw new RuntimeException('Environment, connection, atau configured database tidak memenuhi guard runtime ZR.');
}

$activeDatabase = (string) (DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? '');

if ($activeDatabase === '' || ! hash_equals($expectedDatabase, $activeDatabase)) {
    throw new RuntimeException('Database aktif tidak cocok dengan IST_ZR_RUNTIME_DATABASE; sinkronisasi dihentikan.');
}

$dataset = json_decode(
    (string) file_get_contents(database_path('data/ist-final-staging/zr.json')),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

if (($dataset['subtest_code'] ?? null) !== 'ZR'
    || ($dataset['review_status'] ?? null) !== 'human_review_passed'
    || ($dataset['active'] ?? null) !== false
    || ! is_string($dataset['instruction_content'] ?? null)
    || trim($dataset['instruction_content']) === '') {
    throw new RuntimeException('Canonical staging ZR tidak valid atau belum human-reviewed dan inactive.');
}

$canonicalQuestions = collect($dataset['questions'] ?? [])->keyBy(
    static fn (array $question): string => $question['kind'].'|'.$question['question_number'],
);

if ($canonicalQuestions->count() !== 13
    || $canonicalQuestions->where('kind', 'example')->count() !== 1
    || $canonicalQuestions->where('kind', 'scored')->count() !== 12) {
    throw new RuntimeException('Canonical staging ZR harus memuat satu example dan dua belas scored questions.');
}

$difficultyCounts = ['easy' => 0, 'medium' => 0, 'hard' => 0];
$difficultyWeights = ['easy' => 1, 'medium' => 2, 'hard' => 3];
$weightedMaximum = 0;

foreach ($canonicalQuestions as $canonical) {
    $canonicalAnswer = $canonical['scoring']['canonical_answer'] ?? null;

    if (($canonical['answer_type'] ?? null) !== 'numeric'
        || ($canonical['options'] ?? null) !== []
        || (int) ($canonical['scoring']['max_score'] ?? -1) !== 1
        || ! is_string($canonicalAnswer)
        || preg_match('/\A(?:0|[1-9][0-9]*)\z/', $canonicalAnswer) !== 1) {
        throw new RuntimeException('Canonical staging ZR mempunyai answer type, opsi, atau numeric key yang tidak valid.');
    }

    if (($canonical['kind'] ?? null) === 'scored') {
        $difficulty = $canonical['difficulty_target'] ?? null;

        if (! is_string($difficulty) || ! array_key_exists($difficulty, $difficultyCounts)) {
            throw new RuntimeException('Canonical staging ZR mempunyai difficulty scored yang tidak valid.');
        }

        $difficultyCounts[$difficulty]++;
        $weightedMaximum += $difficultyWeights[$difficulty];
    }
}

if ($difficultyCounts !== ['easy' => 4, 'medium' => 5, 'hard' => 3]
    || $weightedMaximum !== 23) {
    throw new RuntimeException('Difficulty atau weighted maximum canonical ZR tidak sesuai kontrak 4/5/3 dan 23.');
}

$synchronize = static function (bool $write) use ($canonicalQuestions, $dataset): array {
    $subtest = DB::table('ist_subtests')
        ->where('code', 'ZR')
        ->where('is_active', true)
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->first();

    if (! $subtest || (int) $subtest->question_count !== 12 || (int) $subtest->duration_seconds !== 360) {
        throw new RuntimeException('Master subtest ZR aktif tidak sesuai kontrak 12 soal dan 360 detik.');
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
        throw new RuntimeException('Master ZR aktif harus memuat tepat tiga belas record.');
    }

    $questionUpdates = 0;

    foreach ($questions as $question) {
        $key = $question->kind.'|'.$question->question_number;
        $canonical = $canonicalQuestions->get($key);

        if (! is_array($canonical)
            || $question->answer_type !== 'numeric'
            || (int) $question->version !== (int) $canonical['source_version']) {
            throw new RuntimeException("Identitas master ZR {$key} berbeda dari canonical.");
        }

        $activeOptions = DB::table('ist_question_options')
            ->where('ist_question_id', $question->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        if ($activeOptions !== 0) {
            throw new RuntimeException("Master numeric ZR {$key} tidak boleh mempunyai opsi aktif.");
        }

        $expectedDifficulty = $question->kind === 'example'
            ? $question->difficulty
            : $canonical['difficulty_target'];
        $expectedExplanation = $question->kind === 'example'
            ? $canonical['explanation']
            : null;
        $expectedAnswer = $canonical['scoring']['canonical_answer'];
        $currentAnswer = rtrim(rtrim((string) $question->numeric_answer_key, '0'), '.');
        $questionChanged = $question->prompt !== $canonical['prompt']
            || $question->example_explanation !== $expectedExplanation
            || $question->difficulty !== $expectedDifficulty
            || (float) $question->max_score !== (float) $canonical['scoring']['max_score']
            || (int) $question->display_order !== (int) $canonical['display_order']
            || $currentAnswer !== $expectedAnswer;

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
                        'numeric_answer_key' => $expectedAnswer,
                        'display_order' => $canonical['display_order'],
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    return [
        'subtest_updates' => $subtestUpdates,
        'question_updates' => $questionUpdates,
    ];
};

if ($apply) {
    $summary = DB::transaction(static fn (): array => $synchronize(true), 3);
    $postflight = $synchronize(false);

    if (array_sum($postflight) !== 0) {
        throw new RuntimeException('Postflight ZR masih menemukan drift setelah sinkronisasi.');
    }
} else {
    $summary = $synchronize(false);
}

$legacySessions = DB::table('ist_test_questions as tq')
    ->join('ist_test_subtests as ts', 'ts.id', '=', 'tq.ist_test_subtest_id')
    ->join('ist_subtests as s', 's.id', '=', 'ts.ist_subtest_id')
    ->join('ist_tests as t', 't.id', '=', 'ts.ist_test_id')
    ->where('s.code', 'ZR')
    ->where('t.status', 'in_progress')
    ->where('tq.display_order', 1)
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(tq.question_snapshot, '$.prompt')) <> ?", [
        $canonicalQuestions->get('scored|1')['prompt'],
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
echo 'OPTION_UPDATES=0'.PHP_EOL;
echo 'DIFFICULTY=4/5/3'.PHP_EOL;
echo 'WEIGHTED_MAXIMUM='.$weightedMaximum.PHP_EOL;
echo 'LEGACY_IN_PROGRESS_SESSIONS='.$legacySessions.PHP_EOL;
echo 'STATUS=PASS'.PHP_EOL;
