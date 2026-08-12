<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetValidator;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class IstFinalStagingDatasetValidatorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = database_path('data/ist-final-staging');
    }

    public function test_human_reviewed_inactive_stage_17_dataset_passes_staging_validation(): void
    {
        $result = app(IstFinalQuestionDatasetValidator::class)->validateStaging($this->directory);

        $this->assertSame(104, $result->scoredQuestionCount);
        $this->assertSame(9, $result->exampleQuestionCount);
        $this->assertSame(435, $result->optionCount);
        $this->assertCount(144, $result->media);
        $this->assertSame('human_review_passed', $result->manifest['review_status']);
        $this->assertFalse($result->manifest['approved']);
        $this->assertFalse($result->manifest['frozen']);
        $this->assertFalse($result->manifest['active']);
        $this->assertFalse($result->manifest['imported']);
        $this->assertFalse($result->manifest['normative']);
        $this->assertFalse($result->manifest['iq_output']);
        $this->assertSame(
            'mean_of_four_domain_scores',
            $result->manifest['total_internal_score_method']
        );
    }

    public function test_strict_final_validator_rejects_unapproved_unfrozen_staging_dataset(): void
    {
        $this->expectException(InvalidIstFinalQuestionDatasetException::class);

        app(IstFinalQuestionDatasetValidator::class)->validate($this->directory);
    }

    public function test_staging_dry_run_is_read_only_and_confirmed_write_remains_fail_closed(): void
    {
        $exitCode = Artisan::call('ist:import-final-dataset', [
            'path' => $this->directory,
            '--dry-run' => true,
            '--staging' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('human_review_passed', Artisan::output());

        $validatedWithExplicitDatabase = Artisan::call('ist:import-final-dataset', [
            'path' => $this->directory,
            '--dry-run' => true,
            '--staging' => true,
            '--allow-database' => 'tes_iq_testing',
        ]);

        $this->assertSame(0, $validatedWithExplicitDatabase);
        $this->assertStringContainsString(
            'Validasi staging selesai tanpa perubahan database/media.',
            Artisan::output(),
        );

        $refusedWrite = Artisan::call('ist:import-final-dataset', [
            'path' => $this->directory,
            '--staging' => true,
            '--allow-database' => 'tes_iq_testing',
            '--confirm-write' => true,
        ]);

        $this->assertSame(1, $refusedWrite);
        $this->assertStringContainsString(
            'APP_ENV harus local; testing hanya untuk test fixture',
            Artisan::output(),
        );
    }
}
