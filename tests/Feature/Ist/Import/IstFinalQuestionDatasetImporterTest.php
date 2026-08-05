<?php

namespace Tests\Feature\Ist\Import;

use App\Data\Ist\IstFinalMediaStage;
use App\Exceptions\Ist\InvalidIstFinalQuestionDatasetException;
use App\Exceptions\Ist\IstFinalMediaException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Services\Ist\Import\Final\IstFinalEnvironmentGuard;
use App\Services\Ist\Import\Final\IstFinalMediaInstaller;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetImporter;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetValidator;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\Ist\FinalDatasetFactory;

final class IstFinalQuestionDatasetImporterTest extends IstFinalImportTestCase
{
    public function test_dry_run_does_not_change_database_or_media(): void
    {
        $result = app(IstFinalQuestionDatasetImporter::class)->import(
            $this->datasetDirectory,
            dryRun: true,
        );

        $this->assertTrue($result->dryRun);
        $this->assertFalse($result->active);
        $this->assertSame(104, $result->importedQuestions);
        $this->assertSame(9, $result->importedExamples);
        $this->assertSame(0, IstQuestion::query()->count());
        $this->assertSame([], Storage::disk('ist-final-feature')->allFiles());
    }

    public function test_successful_test_import_remains_inactive_and_preserves_development_rows(): void
    {
        $developmentManifestHash = hash_file('sha256', database_path('data/ist-development/manifest.json'));
        $se = IstSubtest::query()->where('code', 'SE')->firstOrFail();
        $development = IstQuestion::query()->create([
            'ist_subtest_id' => $se->id,
            'question_number' => 99,
            'display_order' => 99,
            'kind' => IstQuestion::KIND_SCORED,
            'answer_type' => 'single_choice',
            'prompt' => '[DEV:ist-development] protected test row',
            'max_score' => 1,
            'version' => 900000001,
            'is_active' => false,
        ]);

        $result = app(IstFinalQuestionDatasetImporter::class)->import(
            $this->datasetDirectory,
            'tes_iq_testing',
            false,
        );

        $this->assertFalse($result->dryRun);
        $this->assertFalse($result->active);
        $this->assertSame(104, $result->importedQuestions);
        $this->assertSame(9, $result->importedExamples);
        $this->assertSame(435, $result->importedOptions);
        $this->assertSame(113, IstQuestion::query()->where('version', FinalDatasetFactory::RECORD_VERSION)->count());
        $this->assertSame(0, IstQuestion::query()->where('version', FinalDatasetFactory::RECORD_VERSION)->where('is_active', true)->count());
        $finalQuestionIds = IstQuestion::query()->where('version', FinalDatasetFactory::RECORD_VERSION)->pluck('id');
        $this->assertSame(0, IstQuestionOption::query()->whereIn('ist_question_id', $finalQuestionIds)->where('is_active', true)->count());
        $this->assertTrue(IstQuestion::query()->whereKey($development->id)->exists());
        $this->assertSame($developmentManifestHash, hash_file('sha256', database_path('data/ist-development/manifest.json')));
        $this->assertSame(0, IstSubtest::query()->whereNotNull('instruction_content')->count());
        Storage::disk('ist-final-feature')->assertExists('ist/final/1.0.0-test/options/option-a.svg');
    }

    public function test_reimport_of_the_same_version_is_explicitly_rejected(): void
    {
        $importer = app(IstFinalQuestionDatasetImporter::class);
        $importer->import($this->datasetDirectory, 'tes_iq_testing', false);

        $this->expectException(InvalidIstFinalQuestionDatasetException::class);
        $importer->import($this->datasetDirectory, 'tes_iq_testing', false);
    }

    public function test_publish_failure_compensates_database_and_cleans_staged_media(): void
    {
        $failingInstaller = new class extends IstFinalMediaInstaller
        {
            public function publish(IstFinalMediaStage $stage): void
            {
                throw IstFinalMediaException::because('simulated publish failure');
            }
        };
        $importer = new IstFinalQuestionDatasetImporter(
            app(IstFinalEnvironmentGuard::class),
            app(IstFinalQuestionDatasetValidator::class),
            $failingInstaller,
        );

        try {
            $importer->import($this->datasetDirectory, 'tes_iq_testing', false);
            $this->fail('Publish failure was expected.');
        } catch (IstFinalMediaException) {
            $this->assertSame(0, IstQuestion::query()->where('version', FinalDatasetFactory::RECORD_VERSION)->count());
            $this->assertSame(0, IstQuestionOption::query()->count());
            $this->assertSame([], Storage::disk('ist-final-feature')->allFiles());
        }
    }

    public function test_post_publish_verification_failure_removes_final_media_and_database_rows(): void
    {
        $tamperingInstaller = new class extends IstFinalMediaInstaller
        {
            public function publish(IstFinalMediaStage $stage): void
            {
                Storage::disk($stage->disk)->put($stage->stagingDirectory.'/unexpected.svg', '<svg/>');
                parent::publish($stage);
            }
        };
        $importer = new IstFinalQuestionDatasetImporter(
            app(IstFinalEnvironmentGuard::class),
            app(IstFinalQuestionDatasetValidator::class),
            $tamperingInstaller,
        );

        try {
            $importer->import($this->datasetDirectory, 'tes_iq_testing', false);
            $this->fail('Post-publish verification failure was expected.');
        } catch (IstFinalMediaException) {
            $this->assertSame(0, IstQuestion::query()->where('version', FinalDatasetFactory::RECORD_VERSION)->count());
            $this->assertSame(0, IstQuestionOption::query()->count());
            $this->assertSame([], Storage::disk('ist-final-feature')->allFiles());
        }
    }

    public function test_command_defaults_to_dry_run_without_allow_database(): void
    {
        $this->artisan('ist:import-final-dataset', [
            'path' => $this->datasetDirectory,
            '--question-bank-version' => FinalDatasetFactory::QUESTION_BANK_VERSION,
        ])
            ->expectsOutputToContain('Mode: DRY-RUN')
            ->assertSuccessful();

        $this->assertSame(0, IstQuestion::query()->count());
        $this->assertSame([], Storage::disk('ist-final-feature')->allFiles());
    }

    public function test_command_rejects_mismatched_question_bank_version_without_mutation(): void
    {
        $this->artisan('ist:import-final-dataset', [
            'path' => $this->datasetDirectory,
            '--question-bank-version' => 'different-version',
        ])
            ->expectsOutputToContain('Question bank version berbeda')
            ->assertFailed();

        $this->assertSame(0, IstQuestion::query()->count());
        $this->assertSame([], Storage::disk('ist-final-feature')->allFiles());
    }

    public function test_fixture_contains_no_normative_result_contract(): void
    {
        $manifest = FinalDatasetFactory::readJson($this->datasetDirectory.'/manifest.json');
        $encoded = strtolower(json_encode($manifest, JSON_THROW_ON_ERROR));

        $this->assertNull($manifest['norm_version']);
        foreach (['"iq"', 'gesamt', 'standard_score', 'dominance'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $encoded);
        }
    }
}
