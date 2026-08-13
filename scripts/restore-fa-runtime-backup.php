<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expectedDatabase = trim((string) getenv('IST_FA_RUNTIME_DATABASE'));
$backupPath = trim((string) getenv('IST_FA_RUNTIME_BACKUP'));
$apply = getenv('IST_FA_RUNTIME_APPLY') === '1';
$connection = (string) config('database.default');
$configuredDatabase = (string) config("database.connections.{$connection}.database");

if ($expectedDatabase === '' || $backupPath === '' || ! is_file($backupPath)) {
    throw new RuntimeException('Database target dan file backup runtime FA wajib tersedia.');
}

if (! app()->environment('local')
    || $connection !== 'mysql'
    || ! hash_equals($expectedDatabase, $configuredDatabase)) {
    throw new RuntimeException('Guard environment, connection, atau configured database gagal.');
}

$activeDatabase = (string) (DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? '');
$backup = json_decode((string) file_get_contents($backupPath), true, 512, JSON_THROW_ON_ERROR);

if ($activeDatabase === ''
    || ! hash_equals($expectedDatabase, $activeDatabase)
    || ! hash_equals($expectedDatabase, (string) ($backup['database'] ?? ''))
    || ($backup['subtest']['code'] ?? null) !== 'FA'
    || count($backup['questions'] ?? []) !== 11
    || count($backup['options'] ?? []) !== 55) {
    throw new RuntimeException('Database aktif atau isi backup runtime FA tidak valid.');
}

$subtest = $backup['subtest'];
$questions = collect($backup['questions'])->keyBy('id');
$options = collect($backup['options'])->keyBy('id');
$questionFields = [
    'question_number', 'display_order', 'kind', 'answer_type', 'difficulty',
    'prompt', 'image_disk', 'image_path', 'image_alt', 'example_explanation',
    'numeric_answer_key', 'max_score', 'version', 'is_active', 'updated_at',
];
$optionFields = [
    'option_key', 'option_text', 'image_disk', 'image_path', 'image_alt',
    'display_order', 'is_correct', 'score_value', 'is_active', 'updated_at',
];

$inspect = static function (bool $write) use (
    $subtest,
    $questions,
    $options,
    $questionFields,
    $optionFields,
): array {
    $currentSubtest = DB::table('ist_subtests')
        ->where('id', $subtest['id'])
        ->where('code', 'FA')
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->first();

    if (! $currentSubtest) {
        throw new RuntimeException('Master subtest FA backup tidak ditemukan.');
    }

    $currentQuestions = DB::table('ist_questions')
        ->whereIn('id', $questions->keys())
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->get()
        ->keyBy('id');
    $currentOptions = DB::table('ist_question_options')
        ->whereIn('id', $options->keys())
        ->when($write, static fn ($query) => $query->lockForUpdate())
        ->get()
        ->keyBy('id');

    if ($currentQuestions->count() !== 11 || $currentOptions->count() !== 55) {
        throw new RuntimeException('Natural record runtime FA berbeda dari backup.');
    }

    $summary = ['subtest_updates' => 0, 'question_updates' => 0, 'option_updates' => 0];

    if ($currentSubtest->instruction_content !== $subtest['instruction_content']) {
        $summary['subtest_updates']++;

        if ($write) {
            DB::table('ist_subtests')->where('id', $subtest['id'])->update([
                'instruction_content' => $subtest['instruction_content'],
                'updated_at' => $subtest['updated_at'],
            ]);
        }
    }

    foreach ($questions as $id => $record) {
        $expected = Arr::only($record, $questionFields);
        $current = Arr::only((array) $currentQuestions[$id], $questionFields);

        if ($current !== $expected) {
            $summary['question_updates']++;

            if ($write) {
                DB::table('ist_questions')->where('id', $id)->update($expected);
            }
        }
    }

    foreach ($options as $id => $record) {
        $expected = Arr::only($record, $optionFields);
        $current = Arr::only((array) $currentOptions[$id], $optionFields);

        if ($current !== $expected) {
            $summary['option_updates']++;

            if ($write) {
                DB::table('ist_question_options')->where('id', $id)->update($expected);
            }
        }
    }

    return $summary;
};

$summary = $inspect(false);

if ($apply) {
    $summary = DB::transaction(static fn (): array => $inspect(true), 3);

    if (array_sum($inspect(false)) !== 0) {
        throw new RuntimeException('Postflight restore runtime FA masih menemukan drift.');
    }
}

echo 'ENV='.app()->environment().PHP_EOL;
echo 'CONNECTION='.$connection.PHP_EOL;
echo 'CONFIGURED_DATABASE='.$configuredDatabase.PHP_EOL;
echo 'ACTIVE_DATABASE='.$activeDatabase.PHP_EOL;
echo 'MODE='.($apply ? 'APPLY' : 'DRY_RUN').PHP_EOL;
echo 'SUBTEST_UPDATES='.$summary['subtest_updates'].PHP_EOL;
echo 'QUESTION_UPDATES='.$summary['question_updates'].PHP_EOL;
echo 'OPTION_UPDATES='.$summary['option_updates'].PHP_EOL;
echo 'STATUS=PASS'.PHP_EOL;
