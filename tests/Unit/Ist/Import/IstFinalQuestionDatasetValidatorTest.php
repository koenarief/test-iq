<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetValidator;
use Tests\Fixtures\Ist\FinalDatasetFactory;
use Tests\TestCase;

final class IstFinalQuestionDatasetValidatorTest extends TestCase
{
    private string $directory;

    private IstFinalQuestionDatasetValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = FinalDatasetFactory::create();
        $this->validator = app(IstFinalQuestionDatasetValidator::class);
    }

    protected function tearDown(): void
    {
        FinalDatasetFactory::remove($this->directory);

        parent::tearDown();
    }

    public function test_valid_final_manifest_and_generated_fixture_pass(): void
    {
        $result = $this->validator->validate($this->directory);

        $this->assertSame('tes-kemampuan-kognitif-adaptasi-104', $result->manifest['instrument_identifier']);
        $this->assertSame(104, $result->scoredQuestionCount);
        $this->assertSame(9, $result->exampleQuestionCount);
        $this->assertSame(435, $result->optionCount);
        $this->assertFalse($result->manifest['active']);
    }

    public function test_dataset_type_other_than_final_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'dataset_type' => 'development']);
        $this->assertRejected();
    }

    public function test_wrong_instrument_identifier_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'instrument_identifier' => 'wrong']);
        $this->assertRejected();
    }

    public function test_status_other_than_frozen_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'status' => 'approved']);
        $this->assertRejected();
    }

    public function test_active_manifest_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'active' => true]);
        $this->assertRejected();
    }

    public function test_reserved_development_record_version_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'record_version' => 900000001]);
        $this->assertRejected();
    }

    public function test_empty_version_string_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'question_bank_version' => '']);
        $this->assertRejected();
    }

    public function test_non_null_norm_version_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'norm_version' => 'forbidden']);
        $this->assertRejected();
    }

    public function test_total_scored_other_than_104_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'scored_question_count' => 103]);
        $this->assertRejected();
    }

    public function test_total_examples_other_than_nine_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'example_count' => 8]);
        $this->assertRejected();
    }

    public function test_wrong_count_for_a_subtest_is_rejected(): void
    {
        $this->mutate('manifest.json', function (array $data): array {
            $data['subtests'][0]['scored_question_count'] = 11;

            return $data;
        });
        $this->assertRejected();
    }

    public function test_wrong_subtest_order_is_rejected(): void
    {
        $this->mutate('manifest.json', function (array $data): array {
            [$data['subtest_order'][0], $data['subtest_order'][1]] = [$data['subtest_order'][1], $data['subtest_order'][0]];

            return $data;
        });
        $this->assertRejected();
    }

    public function test_duplicate_subtest_code_is_rejected(): void
    {
        $this->mutate('manifest.json', function (array $data): array {
            $data['subtests'][1]['code'] = 'SE';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_wrong_total_duration_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'duration_seconds' => 2699]);
        $this->assertRejected();
    }

    public function test_wrong_me_phase_is_rejected(): void
    {
        $this->mutate('manifest.json', function (array $data): array {
            $data['subtests'][8]['memorization_duration_seconds'] = 121;

            return $data;
        });
        $this->assertRejected();
    }

    public function test_answer_type_mismatch_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][1]['answer_type'] = 'numeric';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_duplicate_logical_id_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][2]['logical_id'] = $data['questions'][1]['logical_id'];

            return $data;
        });
        $this->assertRejected();
    }

    public function test_duplicate_question_number_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][2]['question_number'] = $data['questions'][1]['question_number'];

            return $data;
        });
        $this->assertRejected();
    }

    public function test_duplicate_display_order_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][2]['display_order'] = $data['questions'][1]['display_order'];

            return $data;
        });
        $this->assertRejected();
    }

    public function test_empty_scored_prompt_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][1]['prompt'] = '   ';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_wrong_difficulty_distribution_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][1]['difficulty_target'] = 'hard';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_ge_without_score_four_is_rejected(): void
    {
        $this->mutateGeOption(0, ['score' => 3, 'correct' => false]);
        $this->assertRejected();
    }

    public function test_ge_without_partial_score_is_rejected(): void
    {
        $this->mutate('ge.json', function (array $data): array {
            foreach ($data['questions'][1]['options'] as &$option) {
                if (! $option['correct']) {
                    $option['score'] = 0;
                }
            }

            return $data;
        });
        $this->assertRejected();
    }

    public function test_ge_score_outside_zero_to_four_is_rejected(): void
    {
        $this->mutateGeOption(1, ['score' => 5]);
        $this->assertRejected();
    }

    public function test_binary_with_two_correct_options_is_rejected(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][1]['options'][1]['correct'] = true;
            $data['questions'][1]['options'][1]['score'] = 1;

            return $data;
        });
        $this->assertRejected();
    }

    public function test_invalid_numeric_key_is_rejected(): void
    {
        $this->mutate('ra.json', function (array $data): array {
            $data['questions'][1]['scoring']['canonical_answer'] = 'sepuluh';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_numeric_multi_key_is_rejected(): void
    {
        $this->mutate('ra.json', function (array $data): array {
            $data['questions'][1]['scoring']['accepted_answers'] = ['10', '01'];

            return $data;
        });
        $this->assertRejected();
    }

    public function test_public_metadata_cannot_contain_scoring_information(): void
    {
        $this->mutate('se.json', function (array $data): array {
            $data['questions'][1]['metadata']['public']['answer_key'] = 'A';

            return $data;
        });
        $this->assertRejected();
    }

    public function test_incomplete_approval_is_rejected(): void
    {
        $this->mutate('approvals.json', function (array $data): array {
            unset($data['language_reviewer']);

            return $data;
        });
        $this->assertRejected();
    }

    public function test_placeholder_approval_is_rejected(): void
    {
        $this->mutate('approvals.json', fn (array $data) => [...$data, 'logic_reviewer' => 'PLACEHOLDER']);
        $this->assertRejected();
    }

    public function test_non_approved_decision_is_rejected(): void
    {
        $this->mutate('approvals.json', fn (array $data) => [...$data, 'decision' => 'pending']);
        $this->assertRejected();
    }

    public function test_checksum_generated_before_freeze_is_rejected(): void
    {
        $checksums = FinalDatasetFactory::readJson($this->directory.'/checksums.json');
        $checksums['generated_at'] = '2026-08-05T00:00:00Z';
        FinalDatasetFactory::writeJson($this->directory.'/checksums.json', $checksums);
        $this->assertRejected(false);
    }

    public function test_file_checksum_mismatch_is_rejected(): void
    {
        file_put_contents($this->directory.'/se.json', "\n", FILE_APPEND);
        $this->assertRejected(false);
    }

    public function test_unlisted_extra_file_is_rejected(): void
    {
        file_put_contents($this->directory.'/unexpected.txt', 'foreign');
        $this->assertRejected(false);
    }

    public function test_template_marker_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'notes' => '[FINAL-TEMPLATE-NOT-ACTIVE]']);
        $this->assertRejected();
    }

    public function test_development_marker_is_rejected(): void
    {
        $this->mutate('manifest.json', fn (array $data) => [...$data, 'notes' => '[DEV:ist-development]']);
        $this->assertRejected();
    }

    private function mutateGeOption(int $index, array $replacement): void
    {
        $this->mutate('ge.json', function (array $data) use ($index, $replacement): array {
            $data['questions'][1]['options'][$index] = [
                ...$data['questions'][1]['options'][$index],
                ...$replacement,
            ];

            return $data;
        });
    }

    private function mutate(string $relativePath, callable $mutation): void
    {
        $path = $this->directory.'/'.$relativePath;
        FinalDatasetFactory::writeJson($path, $mutation(FinalDatasetFactory::readJson($path)));
        FinalDatasetFactory::refreshChecksums($this->directory);
    }

    private function assertRejected(bool $refreshChecksums = true): void
    {
        if ($refreshChecksums) {
            FinalDatasetFactory::refreshChecksums($this->directory);
        }

        $this->expectException(InvalidIstFinalQuestionDatasetException::class);
        $this->validator->validate($this->directory);
    }
}
