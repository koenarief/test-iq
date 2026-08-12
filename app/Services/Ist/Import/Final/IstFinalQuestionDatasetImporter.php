<?php

namespace App\Services\Ist\Import\Final;

use App\Data\Ist\IstFinalDatasetValidationResult;
use App\Data\Ist\IstFinalQuestionDatasetImportResult;
use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use Illuminate\Support\Facades\DB;
use Throwable;

final class IstFinalQuestionDatasetImporter
{
    public function __construct(
        private readonly IstFinalEnvironmentGuard $guard,
        private readonly IstFinalQuestionDatasetValidator $validator,
        private readonly IstFinalMediaInstaller $mediaInstaller,
    ) {}

    public function import(
        string $datasetDirectory,
        ?string $allowDatabase = null,
        bool $dryRun = true,
        bool $allowStaging = false,
    ): IstFinalQuestionDatasetImportResult {
        $dataset = $allowStaging
            ? $this->validator->validateStaging($datasetDirectory)
            : $this->validator->validate($datasetDirectory);
        $testFixture = ($dataset->manifest['test_fixture'] ?? false) === true;
        $context = $this->guard->inspect($allowDatabase, ! $dryRun, $testFixture);

        if (($dataset->manifest['test_fixture'] ?? false) === true
            && $context->environment !== 'testing') {
            $this->fail('test fixture hanya boleh diproses pada APP_ENV=testing');
        }

        $warnings = [
            'Dataset tetap inactive; activation merupakan tindakan terpisah.',
            'Provenance lengkap tetap berada pada frozen dataset ber-checksum; schema runtime hanya menyimpan source version.',
        ];

        if (($dataset->manifest['test_fixture'] ?? false) === true) {
            $warnings[] = 'TEST fixture tidak dapat melewati activation gate.';
        }

        if ($dryRun) {
            return $this->result($dataset, 0, 0, $warnings, true);
        }

        $this->assertDatabaseCanAccept($dataset->manifest['record_version']);
        $stage = $this->mediaInstaller->stage($dataset);
        $createdQuestionIds = [];
        $subtestSnapshots = [];

        try {
            [$questionCount, $optionCount] = DB::transaction(function () use (
                $dataset,
                &$createdQuestionIds,
                &$subtestSnapshots,
            ): array {
                $this->assertDatabaseCanAccept($dataset->manifest['record_version'], true);
                $subtests = IstSubtest::query()
                    ->whereIn('code', array_keys($dataset->subtests))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('code');

                if ($subtests->count() !== 9) {
                    $this->fail('master subtest belum lengkap');
                }

                $manifestSubtests = collect($dataset->manifest['subtests'])->keyBy('code');

                $questionsCreated = 0;
                $optionsCreated = 0;

                foreach ($dataset->subtests as $code => $payload) {
                    /** @var IstSubtest $subtest */
                    $subtest = $subtests[$code];
                    $manifestDefinition = $manifestSubtests[$code] ?? null;

                    if (! is_array($manifestDefinition)) {
                        $this->fail("master definition subtest {$code} tidak lengkap");
                    }

                    $subtestSnapshots[$subtest->id] = $subtest->only($this->masterSubtestFields());
                    $subtest->forceFill([
                    'code' => $code,

                    // name dan sequence berasal dari master IstSubtest yang sudah
                    // dibuat oleh IstSubtestSeeder / IstSubtestCatalog.
                    // Jangan membaca field yang memang tidak ada di manifest final.

                    'question_count' => $manifestDefinition['scored_question_count'],
                    'default_answer_type' => $manifestDefinition['answer_type'],
                    'instruction_content' => trim($payload['instruction_content']),
                    'memorization_content' => $code === 'ME'
                        ? $payload['memorization_content']
                        : null,
                    'duration_seconds' => $manifestDefinition['duration_seconds'],
                    'memorization_seconds' => $manifestDefinition['memorization_duration_seconds'],
                    'answering_seconds' => $manifestDefinition['answering_duration_seconds'],
                ])->save();

                    foreach ($payload['questions'] as $record) {
                        $collision = IstQuestion::withTrashed()
                            ->where('ist_subtest_id', $subtest->id)
                            ->where('kind', $record['kind'])
                            ->where('question_number', $record['question_number'])
                            ->lockForUpdate()
                            ->exists();

                        if ($collision) {
                            $this->fail("natural key {$code}/{$record['kind']}/{$record['question_number']} sudah digunakan; data lama tidak diubah");
                        }

                        $promptMedia = $this->mediaReference($dataset->media, $record['media']['prompt_ref'] ?? null);
                        $question = IstQuestion::query()->create([
                            'ist_subtest_id' => $subtest->id,
                            'question_number' => $record['question_number'],
                            'display_order' => $record['display_order'],
                            'kind' => $record['kind'],
                            'answer_type' => $record['answer_type'],
                            'difficulty' => $record['kind'] === IstQuestion::KIND_SCORED
                            ? $record['difficulty_target']
                            : null,
                            'prompt' => $record['prompt'],
                            'image_disk' => $promptMedia === null ? null : config('ist.final_media_disk', 'public'),
                            'image_path' => $promptMedia['target_path'] ?? null,
                            'image_alt' => $promptMedia['alt_text'] ?? null,
                            'example_explanation' => $record['kind'] === IstQuestion::KIND_EXAMPLE
                                ? $record['explanation']
                                : null,
                            'numeric_answer_key' => $record['answer_type'] === 'numeric'
                                ? $record['scoring']['canonical_answer']
                                : null,
                            'max_score' => $record['scoring']['max_score'],
                            'version' => $dataset->manifest['record_version'],
                            'is_active' => false,
                            'created_by' => null,
                            'updated_by' => null,
                        ]);
                        $createdQuestionIds[] = $question->id;
                        $questionsCreated++;

                        foreach ($record['options'] as $optionRecord) {
                            $optionMedia = $this->mediaReference($dataset->media, $optionRecord['media_ref'] ?? null);
                            IstQuestionOption::query()->create([
                                'ist_question_id' => $question->id,
                                'option_key' => $optionRecord['key'],
                                'option_text' => $optionRecord['text'] ?? null,
                                'image_disk' => $optionMedia === null ? null : config('ist.final_media_disk', 'public'),
                                'image_path' => $optionMedia['target_path'] ?? null,
                                'image_alt' => $optionMedia['alt_text'] ?? null,
                                'display_order' => $optionRecord['display_order'],
                                'is_correct' => $optionRecord['correct'],
                                'score_value' => $optionRecord['score'],
                                'is_active' => false,
                            ]);
                            $optionsCreated++;
                        }
                    }
                }

                return [$questionsCreated, $optionsCreated];
            }, 3);
        } catch (Throwable $exception) {
            $this->mediaInstaller->discard($stage);
            throw $exception;
        }

        try {
            $this->mediaInstaller->publish($stage);
        } catch (Throwable $exception) {
            $this->compensateImport($createdQuestionIds, $subtestSnapshots);
            $this->mediaInstaller->discard($stage);
            throw $exception;
        }

        return $this->result($dataset, $questionCount, $optionCount, $warnings, false);
    }

    private function assertDatabaseCanAccept(int $recordVersion, bool $lock = false): void
    {
        $activeTests = IstTest::query()->whereIn('status', [
            IstTest::STATUS_DRAFT,
            IstTest::STATUS_IN_PROGRESS,
        ]);

        if ($lock) {
            $activeTests->lockForUpdate();
        }

        if ($activeTests->exists()) {
            $this->fail('terdapat sesi draft/in_progress');
        }

        $sameVersion = IstQuestion::withTrashed()->where('version', $recordVersion);

        if ($lock) {
            $sameVersion->lockForUpdate();
        }

        if ($sameVersion->exists()) {
            $this->fail('question bank version sudah pernah diimpor; reimport ditolak eksplisit');
        }
    }

    private function mediaReference(array $media, mixed $logicalId): ?array
    {
        if ($logicalId === null) {
            return null;
        }

        if (! is_string($logicalId) || ! isset($media[$logicalId])) {
            $this->fail('referensi media tidak tersedia');
        }

        return $media[$logicalId];
    }

    private function compensateImport(array $questionIds, array $subtestSnapshots): void
    {
        if ($questionIds === [] && $subtestSnapshots === []) {
            return;
        }

        DB::transaction(function () use ($questionIds, $subtestSnapshots): void {
            if ($questionIds !== []) {
                DB::table('ist_question_options')->whereIn('ist_question_id', $questionIds)->delete();
                DB::table('ist_questions')->whereIn('id', $questionIds)->delete();
            }

            foreach ($subtestSnapshots as $subtestId => $snapshot) {
                DB::table('ist_subtests')->where('id', $subtestId)->update($snapshot);
            }
        }, 3);
    }

    private function masterSubtestFields(): array
    {
        return [
            'code',
            'name',
            'sequence',
            'question_count',
            'default_answer_type',
            'instruction_content',
            'memorization_content',
            'duration_seconds',
            'memorization_seconds',
            'answering_seconds',
            'updated_at',
        ];
    }

    private function result(
        IstFinalDatasetValidationResult $dataset,
        int $questionCount,
        int $optionCount,
        array $warnings,
        bool $dryRun,
    ): IstFinalQuestionDatasetImportResult {
        return new IstFinalQuestionDatasetImportResult(
            instrumentIdentifier: $dataset->manifest['instrument_identifier'],
            questionBankVersion: $dataset->manifest['question_bank_version'],
            recordVersion: $dataset->manifest['record_version'],
            importedQuestions: $dryRun ? $dataset->scoredQuestionCount : $questionCount - $dataset->exampleQuestionCount,
            importedExamples: $dataset->exampleQuestionCount,
            importedOptions: $dryRun ? $dataset->optionCount : $optionCount,
            importedMedia: count($dataset->media),
            active: false,
            dryRun: $dryRun,
            warnings: $warnings,
        );
    }

    private function fail(string $reason): never
    {
        throw InvalidIstFinalQuestionDatasetException::because($reason);
    }
}
