<?php

declare(strict_types=1);

use App\Enums\Ist\IstAccessDestination;
use App\Enums\Ist\IstFinalizationReason;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstMeExampleCompletionService;
use App\Services\Ist\IstQuestionSnapshotService;
use App\Services\Ist\IstSubtestAccessService;
use App\Services\Ist\IstSubtestFinalizationService;
use App\Services\Ist\IstSubtestStartService;
use App\Services\Ist\IstTestLifecycleService;
use App\Http\Presenters\Ist\IstParticipantPayloadPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.default');
$configuredDatabase = config("database.connections.{$connection}.database");
$activeDatabase = DB::selectOne('SELECT DATABASE() AS database_name')->database_name ?? null;

if (app()->environment() !== 'local'
    || $connection !== 'mysql'
    || $configuredDatabase !== 'tes_iq'
    || $activeDatabase !== 'tes_iq') {
    throw new RuntimeException('Guard verifikasi runtime ME gagal.');
}

$dataset = json_decode(
    file_get_contents(dirname(__DIR__).'/database/data/ist-final-staging/me.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
$scoredDataset = array_values(array_filter(
    $dataset['questions'],
    static fn (array $question): bool => $question['kind'] === 'scored',
));
$expectedKeys = array_column(array_map(
    static function (array $question): array {
        $correct = array_values(array_filter(
            $question['options'],
            static fn (array $option): bool => $option['correct'] === true,
        ));

        return [
            'logical_id' => $question['logical_id'],
            'key' => $correct[0]['key'] ?? null,
            'target_word' => $question['metadata']['internal']['target_word'],
        ];
    },
    $scoredDataset,
), null, 'logical_id');

$testCountBefore = DB::table('ist_tests')->count();
$now = CarbonImmutable::parse('2026-08-12 12:00:00', 'UTC');
$report = [];

DB::beginTransaction();

try {
    /** @var IstTestLifecycleService $lifecycle */
    $lifecycle = app(IstTestLifecycleService::class);
    $creation = $lifecycle->create([
        'participant_name' => '__ME_RUNTIME_SYNC_VERIFICATION__',
        'age' => 30,
        'gender' => 'L',
    ]);
    $creation->takeRawAccessToken();
    $test = $creation->test;
    $test->update([
        'status' => IstTest::STATUS_IN_PROGRESS,
        'started_at' => $now->subMinutes(45),
        'current_subtest_sequence' => 9,
    ]);

    foreach ($test->subtests()->where('sequence', '<', 9)->get() as $earlier) {
        $earlier->update([
            'status' => IstTestSubtest::STATUS_COMPLETED,
            'locked_at' => $now->subMinute(),
            'finalized_reason' => IstFinalizationReason::SUBMITTED->value,
            'percentage' => 50,
        ]);
    }

    $runtime = $test->subtests()
        ->with('subtest')
        ->where('sequence', 9)
        ->firstOrFail();
    $runtime->update(['status' => IstTestSubtest::STATUS_INSTRUCTION]);

    /** @var IstQuestionSnapshotService $snapshotService */
    $snapshotService = app(IstQuestionSnapshotService::class);
    $snapshots = $snapshotService->snapshot($runtime);

    if ($snapshots->count() !== 12) {
        throw new RuntimeException('Sesi verifikasi tidak menghasilkan 12 snapshot ME.');
    }

    $difficulty = $snapshots->countBy('difficulty')->all();
    ksort($difficulty, SORT_STRING);

    if ($difficulty !== ['easy' => 4, 'hard' => 3, 'medium' => 5]) {
        throw new RuntimeException('Difficulty snapshot sesi baru tidak sesuai 4/5/3.');
    }

    foreach ($snapshots as $index => $snapshot) {
        $source = $scoredDataset[$index];
        $expected = $expectedKeys[$source['logical_id']];
        $prompt = (string) ($snapshot->question_snapshot['prompt'] ?? '');
        $options = $snapshot->options_snapshot ?? [];

        if (($snapshot->answer_key_snapshot['correct_option_key'] ?? null) !== $expected['key']
            || count($options) !== 5
            || array_column($options, 'option_key') !== ['A', 'B', 'C', 'D', 'E']
            || str_contains($prompt, $expected['target_word'])
            || ! str_contains($prompt, 'huruf permulaan')) {
            throw new RuntimeException("Snapshot sesi baru tidak valid pada {$source['logical_id']}.");
        }
    }

    $firstRuntime = $snapshots->first()->question_snapshot['me_runtime'] ?? null;

    if (! is_array($firstRuntime)
        || array_keys($firstRuntime) !== ['model', 'instructionContent', 'groups']
        || $firstRuntime['model'] !== 'initial_letter_to_category'
        || count($firstRuntime['groups']) !== 5
        || str_contains(strtolower(json_encode($firstRuntime, JSON_THROW_ON_ERROR)), 'pairs')) {
        throw new RuntimeException('Snapshot memorization sesi baru bukan model kategori final.');
    }

    /** @var IstMeExampleCompletionService $exampleService */
    $exampleService = app(IstMeExampleCompletionService::class);
    $runtime = $exampleService->complete($runtime->fresh('subtest'), 'B', $now);

    /** @var IstParticipantPayloadPresenter $presenter */
    $presenter = app(IstParticipantPayloadPresenter::class);
    $instruction = $presenter->instruction(
        $test->fresh(),
        $runtime->fresh('subtest'),
        true,
        true,
    );

    if (! $instruction['canStart']
        || count($instruction['examples']) !== 1
        || ! str_contains((string) $instruction['subtest']['instructionContent'], '5 kelompok')
        || str_contains(strtolower((string) $instruction['subtest']['instructionContent']), 'pasangan kata')
        || str_contains((string) $instruction['examples'][0]['prompt'], 'Pahat')) {
        throw new RuntimeException('Payload instruction sesi baru tidak sesuai kontrak final ME.');
    }

    /** @var IstSubtestStartService $startService */
    $startService = app(IstSubtestStartService::class);
    $runtime = $startService->start($runtime, $now);

    if ($runtime->status !== IstTestSubtest::STATUS_MEMORIZING
        || $runtime->memorization_ends_at?->diffInSeconds($runtime->started_at, true) !== 120.0
        || $runtime->answering_ends_at?->diffInSeconds($runtime->memorization_ends_at, true) !== 240.0) {
        throw new RuntimeException('Deadline sesi baru tidak sesuai 120/240 detik.');
    }

    /** @var IstSubtestAccessService $accessService */
    $accessService = app(IstSubtestAccessService::class);
    $memorizationDecision = $accessService->decide(
        $test->fresh(),
        IstAccessDestination::MEMORIZATION,
        $runtime->fresh(),
        $now,
    );
    $memorizationPayload = $presenter->work(
        $test->fresh(),
        $runtime->fresh('subtest'),
        $memorizationDecision,
        $now,
    );

    if (count($memorizationPayload['memorizationGroups']) !== 5
        || $memorizationPayload['questions'] !== []) {
        throw new RuntimeException('Payload memorization sesi baru tidak sesuai kontrak.');
    }

    $answeringAt = CarbonImmutable::instance(
        $runtime->fresh()->answering_started_at,
    )->addSecond();
    $answeringDecision = $accessService->decide(
        $test->fresh(),
        IstAccessDestination::WORK,
        $runtime->fresh(),
        $answeringAt,
    );
    $answeringPayload = $presenter->work(
        $test->fresh(),
        $runtime->fresh('subtest'),
        $answeringDecision,
        $answeringAt,
    );

    if ($answeringPayload['memorizationGroups'] !== []
        || count($answeringPayload['questions']) !== 12) {
        throw new RuntimeException(sprintf(
            'Payload answering invalid: destination=%s, mode=%s, groups=%d, questions=%d.',
            $answeringDecision->destination->value,
            (string) ($answeringPayload['mode'] ?? 'missing'),
            count($answeringPayload['memorizationGroups']),
            count($answeringPayload['questions']),
        ));
    }

    foreach ($answeringPayload['questions'] as $index => $question) {
        if (str_contains(
            (string) $question['prompt'],
            $expectedKeys[$scoredDataset[$index]['logical_id']]['target_word'],
        )) {
            throw new RuntimeException('Target word bocor pada payload answering sesi baru.');
        }
    }

    /** @var IstSubtestFinalizationService $finalizationService */
    $finalizationService = app(IstSubtestFinalizationService::class);
    $result = $finalizationService->finalize(
        $runtime->fresh(),
        IstFinalizationReason::SUBMITTED,
        $answeringAt,
    );
    $finalRuntime = $runtime->fresh();

    if (! $result->overallCompleted
        || (float) $finalRuntime->max_score !== 23.0
        || $finalRuntime->blank_count !== 12
        || $finalRuntime->partial_count !== 0) {
        throw new RuntimeException('Finalization sesi baru tidak memakai weighted max ME 23.');
    }

    $report = [
        'snapshot_count' => $snapshots->count(),
        'difficulty' => $difficulty,
        'memorization_group_count' => count($memorizationPayload['memorizationGroups']),
        'answering_question_count' => count($answeringPayload['questions']),
        'memorization_seconds' => 120,
        'answering_seconds' => 240,
        'finalized_max_score' => (float) $finalRuntime->max_score,
        'finalized_blank_count' => $finalRuntime->blank_count,
        'finalized_partial_count' => $finalRuntime->partial_count,
    ];
} finally {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
}

if (DB::table('ist_tests')->count() !== $testCountBefore) {
    throw new RuntimeException('Fixture sesi verifikasi tidak ter-rollback.');
}

echo "ME FINAL RUNTIME SESSION VERIFICATION: SUCCESS\n";
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
echo "FIXTURE_ROLLBACK=PASS\n";
