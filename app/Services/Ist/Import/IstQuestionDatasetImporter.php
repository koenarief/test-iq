<?php

namespace App\Services\Ist\Import;

use App\Data\Ist\IstQuestionDatasetImportResult;
use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Exceptions\Ist\IstDevelopmentMediaException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use Illuminate\Support\Facades\DB;
use Throwable;

final class IstQuestionDatasetImporter
{
    public function __construct(
        private readonly IstDevelopmentEnvironmentGuard $guard,
        private readonly IstQuestionDatasetValidator $validator,
        private readonly IstDevelopmentMediaInstaller $mediaInstaller,
    ) {}

    public function import(?string $datasetDirectory = null): IstQuestionDatasetImportResult
    {
        $this->guard->assertSafe();
        $dataset = $this->validator->validate($datasetDirectory);
        $this->assertNoActiveTests();
        $stage = $this->mediaInstaller->stage($dataset);
        $before = [];

        try {
            $summary = DB::transaction(function () use ($dataset, &$before): array {
                $this->assertNoActiveTests(true);

                $subtests = IstSubtest::query()
                    ->whereIn('code', array_keys($dataset['subtests']))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('code');
                if ($subtests->count() !== 9) {
                    $this->fail('master subtest belum lengkap');
                }

                $before = ['subtests' => [], 'questions' => [], 'new_question_ids' => []];
                $created = 0;
                $updated = 0;
                $restored = 0;
                $desiredKeys = [];

                foreach ($dataset['subtests'] as $code => $payload) {
                    /** @var IstSubtest $subtest */
                    $subtest = $subtests[$code];
                    $before['subtests'][$subtest->id] = [
                        'instruction_content' => $subtest->getRawOriginal('instruction_content'),
                        'memorization_content' => $subtest->getRawOriginal('memorization_content'),
                    ];
                    $this->applyDevelopmentContent($subtest, 'instruction_content', $payload['instruction_content']);
                    $this->applyDevelopmentContent($subtest, 'memorization_content', $payload['memorization_content']);
                    $subtest->save();

                    foreach ($payload['questions'] as $questionData) {
                        $natural = $subtest->id.'|'.$questionData['kind'].'|'.$questionData['question_number'];
                        $desiredKeys[$natural] = true;
                        $question = IstQuestion::withTrashed()
                            ->where('ist_subtest_id', $subtest->id)
                            ->where('kind', $questionData['kind'])
                            ->where('question_number', $questionData['question_number'])
                            ->lockForUpdate()
                            ->first();

                        if ($question && ! $this->isDevelopmentOwned($question)) {
                            $this->fail("natural key {$code}/{$questionData['kind']}/{$questionData['question_number']} dimiliki data non-development");
                        }

                        if ($question) {
                            $this->captureQuestion($before, $question);
                            if ($question->trashed()) {
                                $question->restore();
                                $restored++;
                            } else {
                                $updated++;
                            }
                        } else {
                            $question = new IstQuestion;
                            $created++;
                        }

                        $question->fill($this->questionAttributes($subtest->id, $questionData));
                        $question->save();
                        if (! isset($before['questions'][$question->id])) {
                            $before['new_question_ids'][] = $question->id;
                        }
                        $this->syncOptions($question, $questionData['options'], $before);
                    }
                }

                $this->softDeleteOwnedQuestionsMissingFromDataset($subtests->pluck('id')->all(), $desiredKeys, $before);
                $this->auditImportedRows($subtests->pluck('id')->all(), $dataset['counts']);

                return compact('created', 'updated', 'restored');
            }, 3);
        } catch (Throwable $exception) {
            $this->mediaInstaller->discard($stage);
            throw $exception;
        }

        try {
            $this->mediaInstaller->publish($stage);
        } catch (Throwable $publishException) {
            try {
                $this->compensateDatabase($before);
            } catch (Throwable $compensationException) {
                throw IstDevelopmentMediaException::because(
                    'publish gagal dan kompensasi database gagal; diperlukan pemeriksaan manual aman',
                );
            } finally {
                $this->mediaInstaller->discard($stage);
            }

            throw IstDevelopmentMediaException::because('publish gagal; perubahan database telah dikompensasi');
        }

        return new IstQuestionDatasetImportResult(
            dataset: $dataset['manifest']['dataset'],
            recordVersion: $dataset['manifest']['record_version'],
            subtestCount: $dataset['counts']['subtests'],
            scoredQuestionCount: $dataset['counts']['scored_questions'],
            exampleQuestionCount: $dataset['counts']['example_questions'],
            optionCount: $dataset['counts']['options'],
            mediaCount: $dataset['counts']['media'],
            createdQuestionCount: $summary['created'],
            updatedQuestionCount: $summary['updated'],
            restoredQuestionCount: $summary['restored'],
        );
    }

    private function applyDevelopmentContent(IstSubtest $subtest, string $column, mixed $incoming): void
    {
        $current = $subtest->{$column};
        $marker = (string) config('ist.development_marker');

        if ($incoming === null) {
            if ($current !== null && trim((string) $current) !== '' && str_starts_with((string) $current, $marker)) {
                $subtest->{$column} = null;
            }

            return;
        }

        if ($current !== null && trim((string) $current) !== '' && ! str_starts_with((string) $current, $marker)) {
            $this->fail("konten {$column} subtest {$subtest->code} adalah non-development");
        }
        $subtest->{$column} = $incoming;
    }

    private function questionAttributes(int $subtestId, array $data): array
    {
        return [
            'ist_subtest_id' => $subtestId,
            'question_number' => $data['question_number'],
            'display_order' => $data['display_order'],
            'kind' => $data['kind'],
            'answer_type' => $data['answer_type'],
            'prompt' => $data['prompt'],
            'image_disk' => $data['image_disk'],
            'image_path' => $data['image_path'],
            'image_alt' => $data['image_alt'],
            'example_explanation' => $data['example_explanation'],
            'numeric_answer_key' => $data['numeric_answer_key'],
            'max_score' => $data['max_score'],
            'version' => $data['source_version'],
            'is_active' => $data['is_active'],
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    private function syncOptions(IstQuestion $question, array $optionData, array &$before): void
    {
        $desired = [];
        foreach ($optionData as $data) {
            $desired[] = $data['option_key'];
            $option = IstQuestionOption::withTrashed()
                ->where('ist_question_id', $question->id)
                ->where('option_key', $data['option_key'])
                ->lockForUpdate()
                ->first();
            if ($option) {
                if ($option->trashed()) {
                    $option->restore();
                }
            } else {
                $option = new IstQuestionOption;
            }
            $option->fill([
                'ist_question_id' => $question->id,
                'option_key' => $data['option_key'],
                'option_text' => $data['option_text'],
                'image_disk' => $data['image_disk'],
                'image_path' => $data['image_path'],
                'image_alt' => $data['image_alt'],
                'display_order' => $data['display_order'],
                'is_correct' => $data['is_correct'],
                'score_value' => $data['score_value'],
                'is_active' => $data['is_active'],
            ]);
            $option->save();
        }

        IstQuestionOption::query()
            ->where('ist_question_id', $question->id)
            ->when($desired !== [], fn ($query) => $query->whereNotIn('option_key', $desired))
            ->delete();
    }

    private function softDeleteOwnedQuestionsMissingFromDataset(array $subtestIds, array $desiredKeys, array &$before): void
    {
        $questions = IstQuestion::withTrashed()
            ->whereIn('ist_subtest_id', $subtestIds)
            ->whereBetween('version', $this->developmentVersionRange())
            ->where('prompt', 'like', config('ist.development_marker').'%')
            ->lockForUpdate()
            ->get();

        foreach ($questions as $question) {
            $natural = $question->ist_subtest_id.'|'.$question->kind.'|'.$question->question_number;
            if (! isset($desiredKeys[$natural]) && ! $question->trashed()) {
                $this->captureQuestion($before, $question);
                IstQuestionOption::query()->where('ist_question_id', $question->id)->delete();
                $question->delete();
            }
        }
    }

    private function auditImportedRows(array $subtestIds, array $expected): void
    {
        $base = IstQuestion::query()
            ->whereIn('ist_subtest_id', $subtestIds)
            ->whereBetween('version', $this->developmentVersionRange())
            ->where('prompt', 'like', config('ist.development_marker').'%')
            ->where('is_active', true);
        $scored = (clone $base)->where('kind', IstQuestion::KIND_SCORED)->count();
        $examples = (clone $base)->where('kind', IstQuestion::KIND_EXAMPLE)->count();
        $ids = (clone $base)->pluck('id');
        $options = IstQuestionOption::query()->whereIn('ist_question_id', $ids)->where('is_active', true)->count();
        if ($scored !== $expected['scored_questions']
            || $examples !== $expected['example_questions']
            || $options !== $expected['options']) {
            $this->fail("audit pasca-import gagal (scored={$scored}, example={$examples}, options={$options})");
        }
    }

    private function captureQuestion(array &$before, IstQuestion $question): void
    {
        if (isset($before['questions'][$question->id])) {
            return;
        }
        $before['questions'][$question->id] = [
            'attributes' => $question->getRawOriginal(),
            'options' => IstQuestionOption::withTrashed()
                ->where('ist_question_id', $question->id)
                ->get()
                ->map(fn (IstQuestionOption $option): array => $option->getRawOriginal())
                ->all(),
        ];
    }

    /**
     * Filesystem publish happens after the DB commit. If it fails, this restores
     * all rows captured before import and soft-deletes rows newly introduced by
     * the dataset. Media already present before import is never deleted.
     */
    private function compensateDatabase(array $before): void
    {
        DB::transaction(function () use ($before): void {
            $now = now();
            foreach ($before['new_question_ids'] ?? [] as $questionId) {
                DB::table('ist_question_options')->where('ist_question_id', $questionId)->update(['deleted_at' => $now]);
                DB::table('ist_questions')->where('id', $questionId)->update(['deleted_at' => $now, 'is_active' => false]);
            }
            foreach ($before['questions'] ?? [] as $questionId => $snapshot) {
                DB::table('ist_question_options')->where('ist_question_id', $questionId)->update(['deleted_at' => $now]);
                $questionAttributes = $snapshot['attributes'];
                unset($questionAttributes['id']);
                DB::table('ist_questions')->where('id', $questionId)->update($questionAttributes);
                foreach ($snapshot['options'] as $optionAttributes) {
                    $optionId = $optionAttributes['id'];
                    unset($optionAttributes['id']);
                    DB::table('ist_question_options')->where('id', $optionId)->update($optionAttributes);
                }
            }
            foreach ($before['subtests'] ?? [] as $subtestId => $contents) {
                DB::table('ist_subtests')->where('id', $subtestId)->update($contents);
            }
        }, 3);
    }

    private function assertNoActiveTests(bool $lock = false): void
    {
        $query = IstTest::query()->whereIn('status', [IstTest::STATUS_DRAFT, IstTest::STATUS_IN_PROGRESS]);
        if ($lock) {
            $query->lockForUpdate();
        }
        if ($query->exists()) {
            $this->fail('terdapat test IST draft/in_progress');
        }
    }

    private function isDevelopmentOwned(IstQuestion $question): bool
    {
        [$min, $max] = $this->developmentVersionRange();

        return $question->version >= $min
            && $question->version <= $max
            && str_starts_with((string) $question->prompt, (string) config('ist.development_marker'));
    }

    private function developmentVersionRange(): array
    {
        return [(int) config('ist.development_version_min'), (int) config('ist.development_version_max')];
    }

    private function fail(string $reason): never
    {
        throw InvalidIstQuestionDatasetException::because($reason);
    }
}
