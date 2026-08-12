<?php

declare(strict_types=1);

use App\Models\Ist\IstQuestion;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetValidator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$root = dirname(__DIR__);
$datasetDirectory = $root.'/database/data/ist-final-staging';
$backupDirectory = $root.'/database/data/backup-ist-me/runtime-before-final-sync';
$backupPath = $backupDirectory.'/backup.json';
$backupChecksumPath = $backupDirectory.'/backup.sha256';

function meSyncRows(iterable $rows): array
{
    return json_decode(
        json_encode($rows, JSON_THROW_ON_ERROR),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

function meSyncHash(iterable $rows): string
{
    return hash('sha256', json_encode(
        meSyncRows($rows),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    ));
}

function meSyncWrite(string $path, string $contents): void
{
    if (file_exists($path)) {
        throw new RuntimeException("Backup target sudah ada dan tidak boleh ditimpa: {$path}");
    }

    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Gagal menulis backup: {$path}");
    }
}

function meSyncOtherSubtestsHash(int $meSubtestId): string
{
    return hash('sha256', json_encode([
        'subtests' => meSyncRows(DB::table('ist_subtests')
            ->where('id', '!=', $meSubtestId)
            ->orderBy('id')
            ->get()),
        'questions' => meSyncRows(DB::table('ist_questions as q')
            ->join('ist_subtests as s', 's.id', '=', 'q.ist_subtest_id')
            ->where('s.id', '!=', $meSubtestId)
            ->orderBy('q.id')
            ->select('q.*')
            ->get()),
        'options' => meSyncRows(DB::table('ist_question_options as o')
            ->join('ist_questions as q', 'q.id', '=', 'o.ist_question_id')
            ->where('q.ist_subtest_id', '!=', $meSubtestId)
            ->orderBy('o.id')
            ->select('o.*')
            ->get()),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

$environment = app()->environment();
$connection = config('database.default');
$configuredDatabase = config("database.connections.{$connection}.database");
$activeDatabase = DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? null;

if ($environment !== 'local'
    || $connection !== 'mysql'
    || $configuredDatabase !== 'tes_iq'
    || $activeDatabase !== 'tes_iq') {
    throw new RuntimeException('Guard database ME gagal; sinkronisasi dihentikan.');
}

/** @var IstFinalQuestionDatasetValidator $validator */
$validator = app(IstFinalQuestionDatasetValidator::class);
$dataset = $validator->validateStaging($datasetDirectory);
$payload = $dataset->subtests['ME'] ?? null;

if (! is_array($payload)
    || ($payload['question_bank_version'] ?? null) !== '2026.08.06-stage17'
    || ($dataset->manifest['record_version'] ?? null) !== 100000017) {
    throw new RuntimeException('Source of truth ME atau version staging tidak valid.');
}

$subtest = DB::table('ist_subtests')->where('code', 'ME')->first();

if ($subtest === null) {
    throw new RuntimeException('Master subtest ME tidak ditemukan.');
}

if ((int) $subtest->question_count !== 12
    || (int) $subtest->duration_seconds !== 360
    || (int) $subtest->memorization_seconds !== 120
    || (int) $subtest->answering_seconds !== 240) {
    throw new RuntimeException('Timer atau question count master ME tidak sesuai kontrak.');
}

$questions = DB::table('ist_questions')
    ->where('ist_subtest_id', $subtest->id)
    ->orderBy('id')
    ->get();
$questionIds = $questions->pluck('id')->all();
$options = DB::table('ist_question_options')
    ->whereIn('ist_question_id', $questionIds)
    ->orderBy('id')
    ->get();

if ($questions->count() !== 13
    || $questions->whereNull('deleted_at')->count() !== 13
    || $options->count() !== 65
    || $options->whereNull('deleted_at')->count() !== 65) {
    throw new RuntimeException('Jumlah row master ME tidak aman untuk sinkronisasi in-place.');
}

$activeTests = DB::table('ist_tests')
    ->whereIn('status', ['draft', 'in_progress'])
    ->orderBy('id')
    ->get(['id', 'status']);
$activeRuntimes = DB::table('ist_test_subtests as ts')
    ->join('ist_tests as t', 't.id', '=', 'ts.ist_test_id')
    ->where('ts.ist_subtest_id', $subtest->id)
    ->whereIn('t.status', ['draft', 'in_progress'])
    ->orderBy('ts.id')
    ->select([
        'ts.id',
        'ts.ist_test_id',
        'ts.status',
        'ts.question_count',
    ])
    ->get();

foreach ($activeRuntimes as $runtime) {
    $snapshotCount = DB::table('ist_test_questions')
        ->where('ist_test_subtest_id', $runtime->id)
        ->count();

    if ($snapshotCount !== (int) $runtime->question_count) {
        throw new RuntimeException(
            "Sesi aktif {$runtime->ist_test_id} mempunyai snapshot ME tidak lengkap; sinkronisasi dihentikan."
        );
    }
}

$snapshotRows = DB::table('ist_test_questions as tq')
    ->join('ist_test_subtests as ts', 'ts.id', '=', 'tq.ist_test_subtest_id')
    ->where('ts.ist_subtest_id', $subtest->id)
    ->orderBy('tq.id')
    ->select('tq.*')
    ->get();
$snapshotHashBefore = meSyncHash($snapshotRows);
$otherSubtestsHashBefore = meSyncOtherSubtestsHash((int) $subtest->id);

if (is_dir($backupDirectory) || file_exists($backupDirectory)) {
    throw new RuntimeException(
        "Folder backup sudah ada dan tidak boleh ditimpa: {$backupDirectory}"
    );
}

if (! mkdir($backupDirectory, 0750, true) && ! is_dir($backupDirectory)) {
    throw new RuntimeException("Gagal membuat folder backup: {$backupDirectory}");
}

$backup = json_encode([
    'backup_type' => 'ist_me_runtime_before_final_sync',
    'created_at' => now()->toIso8601String(),
    'environment' => $environment,
    'connection' => $connection,
    'database' => $activeDatabase,
    'source_dataset' => 'database/data/ist-final-staging/me.json',
    'source_question_bank_version' => $payload['question_bank_version'],
    'source_record_version' => $dataset->manifest['record_version'],
    'row_counts' => [
        'subtests' => 1,
        'questions' => $questions->count(),
        'options' => $options->count(),
        'existing_snapshot_rows_not_modified' => $snapshotRows->count(),
        'active_tests_not_modified' => $activeTests->count(),
    ],
    'snapshot_integrity_sha256' => $snapshotHashBefore,
    'subtest' => meSyncRows([$subtest])[0],
    'questions' => meSyncRows($questions),
    'options' => meSyncRows($options),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";

meSyncWrite($backupPath, $backup);
$backupChecksum = hash_file('sha256', $backupPath);

if ($backupChecksum === false) {
    throw new RuntimeException('Gagal menghitung checksum backup ME.');
}

meSyncWrite($backupChecksumPath, $backupChecksum."  backup.json\n");

$updatedAt = now();

DB::transaction(function () use (
    $subtest,
    $payload,
    $dataset,
    $updatedAt,
): void {
    $lockedSubtest = DB::table('ist_subtests')
        ->where('id', $subtest->id)
        ->lockForUpdate()
        ->first();

    if ($lockedSubtest === null || $lockedSubtest->code !== 'ME') {
        throw new RuntimeException('Master ME berubah sebelum transaksi.');
    }

    $activeRuntimes = DB::table('ist_test_subtests as ts')
        ->join('ist_tests as t', 't.id', '=', 'ts.ist_test_id')
        ->where('ts.ist_subtest_id', $subtest->id)
        ->whereIn('t.status', ['draft', 'in_progress'])
        ->lockForUpdate()
        ->select(['ts.id', 'ts.ist_test_id', 'ts.question_count'])
        ->get();

    foreach ($activeRuntimes as $runtime) {
        $snapshotCount = DB::table('ist_test_questions')
            ->where('ist_test_subtest_id', $runtime->id)
            ->count();

        if ($snapshotCount !== (int) $runtime->question_count) {
            throw new RuntimeException(
                "Snapshot sesi aktif {$runtime->ist_test_id} berubah sebelum transaksi."
            );
        }
    }

    DB::table('ist_subtests')
        ->where('id', $subtest->id)
        ->update([
            'instruction_content' => trim($payload['instruction_content']),
            'memorization_content' => $payload['memorization_content'],
            'updated_at' => $updatedAt,
        ]);

    foreach ($payload['questions'] as $record) {
        $question = DB::table('ist_questions')
            ->where('ist_subtest_id', $subtest->id)
            ->where('kind', $record['kind'])
            ->where('question_number', $record['question_number'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if ($question === null) {
            throw new RuntimeException(
                "Natural key ME {$record['kind']}/{$record['question_number']} tidak ditemukan."
            );
        }

        DB::table('ist_questions')
            ->where('id', $question->id)
            ->update([
                'display_order' => $record['display_order'],
                'answer_type' => $record['answer_type'],
                'prompt' => $record['prompt'],
                'image_disk' => null,
                'image_path' => null,
                'image_alt' => null,
                'example_explanation' => $record['kind'] === IstQuestion::KIND_EXAMPLE
                    ? $record['explanation']
                    : null,
                'numeric_answer_key' => null,
                'max_score' => $record['scoring']['max_score'],
                'difficulty' => $record['kind'] === IstQuestion::KIND_SCORED
                    ? $record['difficulty_target']
                    : $question->difficulty,
                'version' => $dataset->manifest['record_version'],
                'is_active' => $question->is_active,
                'updated_at' => $updatedAt,
            ]);

        $existingOptions = DB::table('ist_question_options')
            ->where('ist_question_id', $question->id)
            ->whereNull('deleted_at')
            ->orderBy('display_order')
            ->lockForUpdate()
            ->get()
            ->keyBy('option_key');

        if ($existingOptions->count() !== 5
            || $existingOptions->keys()->sort()->values()->all() !== ['A', 'B', 'C', 'D', 'E']) {
            throw new RuntimeException("Opsi master {$record['logical_id']} tidak aman untuk update in-place.");
        }

        foreach ($record['options'] as $optionRecord) {
            $option = $existingOptions[$optionRecord['key']] ?? null;

            if ($option === null) {
                throw new RuntimeException("Opsi {$record['logical_id']}/{$optionRecord['key']} tidak ditemukan.");
            }

            DB::table('ist_question_options')
                ->where('id', $option->id)
                ->update([
                    'option_text' => $optionRecord['text'],
                    'image_disk' => null,
                    'image_path' => null,
                    'image_alt' => null,
                    'display_order' => $optionRecord['display_order'],
                    'is_correct' => $optionRecord['correct'],
                    'score_value' => $optionRecord['score'],
                    'is_active' => $option->is_active,
                    'updated_at' => $updatedAt,
                ]);
        }
    }
}, 3);

$snapshotRowsAfter = DB::table('ist_test_questions as tq')
    ->join('ist_test_subtests as ts', 'ts.id', '=', 'tq.ist_test_subtest_id')
    ->where('ts.ist_subtest_id', $subtest->id)
    ->orderBy('tq.id')
    ->select('tq.*')
    ->get();
$snapshotHashAfter = meSyncHash($snapshotRowsAfter);
$otherSubtestsHashAfter = meSyncOtherSubtestsHash((int) $subtest->id);

if ($snapshotHashAfter !== $snapshotHashBefore) {
    throw new RuntimeException('Snapshot sesi lama berubah; verifikasi gagal.');
}

if ($otherSubtestsHashAfter !== $otherSubtestsHashBefore) {
    throw new RuntimeException('Data subtest selain ME berubah; verifikasi gagal.');
}

echo "ME FINAL RUNTIME SYNC: SUCCESS\n";
echo "ENV={$environment}\n";
echo "DB_CONNECTION={$connection}\n";
echo "DB_DATABASE={$activeDatabase}\n";
echo "ACTIVE_TESTS_PRESERVED={$activeTests->count()}\n";
echo "BACKUP={$backupPath}\n";
echo "BACKUP_SHA256={$backupChecksum}\n";
echo "ROWS_UPDATED=subtests:1,questions:13,options:65\n";
echo "LEGACY_SNAPSHOT_ROWS_PRESERVED={$snapshotRowsAfter->count()}\n";
echo "OTHER_SUBTESTS_UNCHANGED=YES\n";
