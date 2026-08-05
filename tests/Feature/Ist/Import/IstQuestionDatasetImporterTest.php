<?php

namespace Tests\Feature\Ist\Import;

use App\Data\Ist\IstDevelopmentMediaStage;
use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Exceptions\Ist\IstDevelopmentMediaException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Services\Ist\Import\IstDevelopmentEnvironmentGuard;
use App\Services\Ist\Import\IstDevelopmentMediaInstaller;
use App\Services\Ist\Import\IstQuestionDatasetImporter;
use App\Services\Ist\Import\IstQuestionDatasetValidator;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class IstQuestionDatasetImporterTest extends IstDevelopmentImportTestCase
{
    public function test_importer_creates_exact_owned_dataset_and_maps_source_version(): void
    {
        $result = app(IstQuestionDatasetImporter::class)->import();

        $this->assertSame(104, $result->scoredQuestionCount);
        $this->assertSame(9, $result->exampleQuestionCount);
        $this->assertSame(435, $result->optionCount);
        $this->assertSame(113, $result->createdQuestionCount);
        $this->assertSame(113, IstQuestion::query()->count());
        $this->assertSame(435, IstQuestionOption::query()->count());
        $this->assertSame([900000001], IstQuestion::query()->distinct()->pluck('version')->all());
        $this->assertSame(0, IstQuestion::query()->where('prompt', 'like', '%expected-results%')->count());
        Storage::disk('ist-development-feature')->assertExists('ist-development/v1/dev-prompt.svg');

        $me = IstSubtest::query()->where('code', 'ME')->firstOrFail();
        $this->assertStringStartsWith('[DEV:ist-development]', $me->memorization_content);
        $this->assertSame(0, IstSubtest::query()->where('code', '!=', 'ME')->whereNotNull('memorization_content')->count());
    }

    public function test_import_is_idempotent_and_preserves_natural_ids(): void
    {
        app(IstQuestionDatasetImporter::class)->import();
        $ids = IstQuestion::query()->orderBy('id')->pluck('id')->all();

        $second = app(IstQuestionDatasetImporter::class)->import();

        $this->assertSame(0, $second->createdQuestionCount);
        $this->assertSame(113, $second->updatedQuestionCount);
        $this->assertSame($ids, IstQuestion::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame(435, IstQuestionOption::query()->count());
    }

    public function test_soft_deleted_owned_rows_are_restored_but_non_development_natural_key_is_rejected(): void
    {
        app(IstQuestionDatasetImporter::class)->import();
        $owned = IstQuestion::query()->firstOrFail();
        $owned->options()->delete();
        $owned->delete();

        $result = app(IstQuestionDatasetImporter::class)->import();
        $this->assertSame(1, $result->restoredQuestionCount);

        $question = IstQuestion::query()->where('ist_subtest_id', $owned->ist_subtest_id)
            ->where('kind', $owned->kind)->where('question_number', $owned->question_number)->firstOrFail();
        $question->update(['version' => 1, 'prompt' => 'Final protected question']);

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetImporter::class)->import();
    }

    public function test_marker_without_reserved_version_and_reserved_version_without_marker_are_not_owned(): void
    {
        $se = IstSubtest::query()->where('code', 'SE')->firstOrFail();
        IstQuestion::query()->create([
            'ist_subtest_id' => $se->id,
            'question_number' => 1,
            'display_order' => 1,
            'kind' => IstQuestion::KIND_SCORED,
            'answer_type' => 'single_choice',
            'prompt' => '[DEV:ist-development] marker only',
            'max_score' => 1,
            'version' => 1,
            'is_active' => true,
        ]);

        try {
            app(IstQuestionDatasetImporter::class)->import();
            $this->fail('Marker-only row must be rejected.');
        } catch (InvalidIstQuestionDatasetException) {
            $this->addToAssertionCount(1);
        }

        IstQuestion::query()->delete();
        IstQuestion::withTrashed()->firstOrFail()->forceDelete();
        IstQuestion::query()->create([
            'ist_subtest_id' => $se->id,
            'question_number' => 1,
            'display_order' => 1,
            'kind' => IstQuestion::KIND_SCORED,
            'answer_type' => 'single_choice',
            'prompt' => 'reserved version only',
            'max_score' => 1,
            'version' => 900000001,
            'is_active' => true,
        ]);

        $this->expectException(InvalidIstQuestionDatasetException::class);
        app(IstQuestionDatasetImporter::class)->import();
    }

    public function test_active_session_blocks_import_before_mutation(): void
    {
        IstTest::query()->create([
            'public_id' => '00000000-0000-4000-8000-000000000001',
            'participant_name' => 'DEV participant',
            'age' => 20,
            'gender' => 'L',
            'status' => IstTest::STATUS_DRAFT,
            'current_subtest_sequence' => 1,
        ]);

        try {
            app(IstQuestionDatasetImporter::class)->import();
            $this->fail('Active session must block import.');
        } catch (InvalidIstQuestionDatasetException) {
            $this->assertSame(0, IstQuestion::query()->count());
            Storage::disk('ist-development-feature')->assertMissing('ist-development/v1/dev-prompt.svg');
        }
    }

    public function test_non_development_subtest_content_blocks_and_rolls_back_everything(): void
    {
        IstSubtest::query()->where('code', 'WA')->update(['instruction_content' => 'Final instruction']);

        try {
            app(IstQuestionDatasetImporter::class)->import();
            $this->fail('Final content must block import.');
        } catch (InvalidIstQuestionDatasetException) {
            $this->assertSame(0, IstQuestion::query()->count());
            $this->assertSame('Final instruction', IstSubtest::query()->where('code', 'WA')->value('instruction_content'));
            $this->assertNull(IstSubtest::query()->where('code', 'SE')->value('instruction_content'));
        }
    }

    public function test_publish_failure_compensates_database_and_discards_staging(): void
    {
        $failingInstaller = new class extends IstDevelopmentMediaInstaller
        {
            public function publish(IstDevelopmentMediaStage $stage): void
            {
                throw new RuntimeException('simulated publish failure');
            }
        };
        $importer = new IstQuestionDatasetImporter(
            app(IstDevelopmentEnvironmentGuard::class),
            app(IstQuestionDatasetValidator::class),
            $failingInstaller,
        );

        try {
            $importer->import();
            $this->fail('Publish failure was expected.');
        } catch (IstDevelopmentMediaException) {
            $this->assertSame(0, IstQuestion::query()->count());
            $this->assertSame(0, IstQuestionOption::query()->count());
            $this->assertSame(0, IstSubtest::query()->whereNotNull('instruction_content')->count());
            $this->assertSame([], Storage::disk('ist-development-feature')->allFiles());
        }
    }
}
