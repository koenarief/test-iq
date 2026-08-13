<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expectedDatabase = trim((string) getenv('IST_FA_RUNTIME_DATABASE'));
$apply = getenv('IST_FA_RUNTIME_APPLY') === '1';
$allowInReview = getenv('IST_FA_ALLOW_IN_REVIEW') === '1';
$connection = (string) config('database.default');
$configuredDatabase = (string) config("database.connections.{$connection}.database");

if ($expectedDatabase === '') {
    throw new RuntimeException('IST_FA_RUNTIME_DATABASE wajib diisi dengan database target eksplisit.');
}

if (! app()->environment('local')
    || $connection !== 'mysql'
    || ! hash_equals($expectedDatabase, $configuredDatabase)) {
    throw new RuntimeException('Guard environment, connection, atau configured database FA gagal.');
}

$activeDatabase = (string) (DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? '');

if ($activeDatabase === '' || ! hash_equals($expectedDatabase, $activeDatabase)) {
    throw new RuntimeException('Database aktif tidak cocok dengan IST_FA_RUNTIME_DATABASE.');
}

$staging = $root.'/database/data/ist-final-staging';
$dataset = json_decode((string) file_get_contents($staging.'/fa.json'), true, 512, JSON_THROW_ON_ERROR);
$metadata = json_decode((string) file_get_contents($staging.'/media/metadata.json'), true, 512, JSON_THROW_ON_ERROR);

if (($dataset['subtest_code'] ?? null) !== 'FA'
    || ($dataset['review_status'] ?? null) !== 'in_review'
    || ($dataset['active'] ?? null) !== false
    || ! $allowInReview) {
    throw new RuntimeException('Kandidat FA harus in_review, inactive, dan diizinkan eksplisit untuk UAT.');
}

$questions = collect($dataset['questions'] ?? [])->keyBy(
    static fn (array $question): string => $question['kind'].'|'.$question['question_number'],
);
$media = collect($metadata['media'] ?? [])->where('subtest_code', 'FA')->keyBy('logical_id');
$difficultyCounts = ['easy' => 0, 'medium' => 0, 'hard' => 0];
$weightedMaximum = 0;
$weights = ['easy' => 1, 'medium' => 2, 'hard' => 3];

if ($questions->count() !== 11
    || $questions->where('kind', 'example')->count() !== 1
    || $questions->where('kind', 'scored')->count() !== 10
    || $media->count() !== 66) {
    throw new RuntimeException('Kandidat FA harus berisi 1 example, 10 scored, dan 66 media.');
}

foreach ($questions as $question) {
    $options = collect($question['options'] ?? []);
    $correct = $options->where('correct', true);

    if (($question['answer_type'] ?? null) !== 'image_choice'
        || $options->count() !== 5
        || $options->pluck('key')->all() !== ['A', 'B', 'C', 'D', 'E']
        || $correct->count() !== 1
        || $options->contains(static fn (array $option): bool => (int) $option['score'] !== ($option['correct'] ? 1 : 0))) {
        throw new RuntimeException("Kontrak opsi kandidat FA {$question['logical_id']} tidak valid.");
    }

    if ($question['kind'] === 'scored') {
        $difficulty = $question['difficulty_target'] ?? null;

        if (! is_string($difficulty) || ! isset($weights[$difficulty])) {
            throw new RuntimeException("Difficulty kandidat FA {$question['logical_id']} tidak valid.");
        }

        $difficultyCounts[$difficulty]++;
        $weightedMaximum += $weights[$difficulty];
    }
}

if ($difficultyCounts !== ['easy' => 3, 'medium' => 4, 'hard' => 3] || $weightedMaximum !== 20) {
    throw new RuntimeException('Difficulty atau weighted maximum kandidat FA tidak sesuai 3/4/3 dan 20.');
}

$runtimePrefix = 'ist/uat/2026.08.13-fa-source-117-126/fa';
$runtimeMedia = static function (string $logicalId) use ($media, $staging, $runtimePrefix): array {
    $record = $media->get($logicalId);

    if (! is_array($record) || ! is_string($record['relative_path'] ?? null)) {
        throw new RuntimeException("Media FA {$logicalId} tidak tersedia.");
    }

    $source = $staging.'/'.$record['relative_path'];
    $relative = preg_replace('#^media/fa/#', '', $record['relative_path']);

    if (! is_string($relative) || ! is_file($source) || hash_file('sha256', $source) !== $record['sha256']) {
        throw new RuntimeException("Checksum media FA {$logicalId} tidak valid.");
    }

    return [
        'source' => $source,
        'relative' => $runtimePrefix.'/'.$relative,
        'absolute' => storage_path('app/public/'.$runtimePrefix.'/'.$relative),
    ];
};

$synchronize = static function (bool $write) use ($dataset, $questions, $runtimeMedia): array {
    $subtest = DB::table('ist_subtests')
        ->where('code', 'FA')
        ->where('is_active', true)
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->first();

    if (! $subtest || (int) $subtest->question_count !== 10 || (int) $subtest->duration_seconds !== 240) {
        throw new RuntimeException('Master FA aktif tidak sesuai kontrak 10 soal dan 240 detik.');
    }

    $masterQuestions = DB::table('ist_questions')
        ->where('ist_subtest_id', $subtest->id)
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->get();

    if ($masterQuestions->count() !== 11) {
        throw new RuntimeException('Master FA aktif harus mempunyai tepat 11 record.');
    }

    $summary = ['subtest_updates' => 0, 'question_updates' => 0, 'option_updates' => 0];

    if ($subtest->instruction_content !== $dataset['instruction_content']) {
        $summary['subtest_updates']++;

        if ($write) {
            DB::table('ist_subtests')->where('id', $subtest->id)->update([
                'instruction_content' => $dataset['instruction_content'],
                'updated_at' => now(),
            ]);
        }
    }

    foreach ($masterQuestions as $masterQuestion) {
        $key = $masterQuestion->kind.'|'.$masterQuestion->question_number;
        $canonical = $questions->get($key);

        if (! is_array($canonical) || $masterQuestion->answer_type !== 'image_choice') {
            throw new RuntimeException("Identitas master FA {$key} berbeda dari kandidat.");
        }

        $promptMedia = $runtimeMedia($canonical['media']['prompt_ref']);
        // Schema runtime mewajibkan difficulty non-null. Difficulty example tidak
        // berkontribusi pada skor, sehingga nilai master lama dipertahankan.
        $difficulty = $canonical['kind'] === 'example'
            ? $masterQuestion->difficulty
            : $canonical['difficulty_target'];
        $explanation = $canonical['kind'] === 'example' ? $canonical['explanation'] : null;
        $questionChanged = $masterQuestion->prompt !== $canonical['prompt']
            || $masterQuestion->example_explanation !== $explanation
            || $masterQuestion->difficulty !== $difficulty
            || (int) $masterQuestion->display_order !== (int) $canonical['display_order']
            || (float) $masterQuestion->max_score !== 1.0
            || $masterQuestion->image_disk !== 'public'
            || $masterQuestion->image_path !== $promptMedia['relative'];

        if ($questionChanged) {
            $summary['question_updates']++;

            if ($write) {
                DB::table('ist_questions')->where('id', $masterQuestion->id)->update([
                    'prompt' => $canonical['prompt'],
                    'example_explanation' => $explanation,
                    'difficulty' => $difficulty,
                    'display_order' => $canonical['display_order'],
                    'max_score' => 1,
                    'image_disk' => 'public',
                    'image_path' => $promptMedia['relative'],
                    'image_alt' => $canonical['kind'] === 'example'
                        ? 'Potongan bentuk contoh FA.'
                        : 'Potongan bentuk soal FA '.(int) $canonical['display_order'].'.',
                    'updated_at' => now(),
                ]);
            }
        }

        $masterOptions = DB::table('ist_question_options')
            ->where('ist_question_id', $masterQuestion->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->when($write, static fn ($query) => $query->lockForUpdate())
            ->orderBy('display_order')
            ->get();

        if ($masterOptions->count() !== 5 || $masterOptions->pluck('option_key')->all() !== ['A', 'B', 'C', 'D', 'E']) {
            throw new RuntimeException("Master FA {$key} tidak mempunyai tepat opsi A-E.");
        }

        foreach ($masterOptions as $index => $masterOption) {
            $canonicalOption = $canonical['options'][$index];
            $optionMedia = $runtimeMedia($canonicalOption['media_ref']);
            $optionChanged = $masterOption->option_text !== null
                || (int) $masterOption->display_order !== (int) $canonicalOption['display_order']
                || (bool) $masterOption->is_correct !== (bool) $canonicalOption['correct']
                || (float) $masterOption->score_value !== (float) $canonicalOption['score']
                || $masterOption->image_disk !== 'public'
                || $masterOption->image_path !== $optionMedia['relative'];

            if ($optionChanged) {
                $summary['option_updates']++;

                if ($write) {
                    DB::table('ist_question_options')->where('id', $masterOption->id)->update([
                        'option_text' => null,
                        'display_order' => $canonicalOption['display_order'],
                        'is_correct' => $canonicalOption['correct'],
                        'score_value' => $canonicalOption['score'],
                        'image_disk' => 'public',
                        'image_path' => $optionMedia['relative'],
                        'image_alt' => "Pilihan FA {$canonicalOption['key']}.",
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    return $summary;
};

$summary = $synchronize(false);
$backupPath = null;

if ($apply) {
    $subtest = DB::table('ist_subtests')->where('code', 'FA')->first();
    $masterQuestions = DB::table('ist_questions')->where('ist_subtest_id', $subtest->id)->whereNull('deleted_at')->get();
    $questionIds = $masterQuestions->pluck('id');
    $backup = [
        'database' => $activeDatabase,
        'created_at' => now()->toIso8601String(),
        'subtest' => (array) $subtest,
        'questions' => $masterQuestions->map(static fn ($record): array => (array) $record)->all(),
        'options' => DB::table('ist_question_options')->whereIn('ist_question_id', $questionIds)->whereNull('deleted_at')->get()
            ->map(static fn ($record): array => (array) $record)->all(),
    ];
    $backupDirectory = storage_path('app/private/ist-backups');

    if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0775, true) && ! is_dir($backupDirectory)) {
        throw new RuntimeException('Direktori backup runtime FA tidak dapat dibuat.');
    }

    $backupPath = $backupDirectory.'/fa-before-source-117-126-'.now()->format('Ymd-His').'.json';
    file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

    foreach ($questions as $canonical) {
        $refs = [$canonical['media']['prompt_ref'], ...array_column($canonical['options'], 'media_ref')];

        foreach ($refs as $ref) {
            $runtime = $runtimeMedia($ref);
            $directory = dirname($runtime['absolute']);

            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException("Direktori media runtime FA tidak dapat dibuat: {$directory}");
            }

            if (! copy($runtime['source'], $runtime['absolute'])) {
                throw new RuntimeException("Media runtime FA gagal disalin: {$ref}");
            }
        }
    }

    $summary = DB::transaction(static fn (): array => $synchronize(true), 3);
    $postflight = $synchronize(false);

    if (array_sum($postflight) !== 0) {
        throw new RuntimeException('Postflight FA masih menemukan drift setelah sinkronisasi.');
    }
}

$legacySessions = DB::table('ist_test_subtests as ts')
    ->join('ist_subtests as st', 'st.id', '=', 'ts.ist_subtest_id')
    ->join('ist_tests as t', 't.id', '=', 'ts.ist_test_id')
    ->where('st.code', 'FA')
    ->whereIn('t.status', ['draft', 'in_progress'])
    ->count();

echo 'ENV='.app()->environment().PHP_EOL;
echo 'CONNECTION='.$connection.PHP_EOL;
echo 'CONFIGURED_DATABASE='.$configuredDatabase.PHP_EOL;
echo 'ACTIVE_DATABASE='.$activeDatabase.PHP_EOL;
echo 'MODE='.($apply ? 'APPLY' : 'DRY_RUN').PHP_EOL;
echo 'SUBTEST_UPDATES='.$summary['subtest_updates'].PHP_EOL;
echo 'QUESTION_UPDATES='.$summary['question_updates'].PHP_EOL;
echo 'OPTION_UPDATES='.$summary['option_updates'].PHP_EOL;
echo 'MEDIA_COUNT=66'.PHP_EOL;
echo 'DIFFICULTY=3/4/3'.PHP_EOL;
echo 'WEIGHTED_MAXIMUM='.$weightedMaximum.PHP_EOL;
echo 'EXISTING_OPEN_SESSION_SUBTESTS='.$legacySessions.PHP_EOL;
echo 'BACKUP='.($backupPath ?? 'not-created-in-dry-run').PHP_EOL;
echo 'STATUS=PASS'.PHP_EOL;
