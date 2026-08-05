<?php

namespace Tests\Feature\Ist\Import;

use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Services\Ist\Import\IstDevelopmentEnvironmentGuard;
use App\Services\Ist\Import\IstQuestionDatasetImporter;
use Database\Seeders\Ist\IstDevelopmentQuestionSeeder;
use Illuminate\Support\Facades\Artisan;

final class IstDevelopmentSeederAndCleanupTest extends IstDevelopmentImportTestCase
{
    public function test_manual_seeder_uses_guarded_importer(): void
    {
        $seeder = new IstDevelopmentQuestionSeeder;
        $seeder->run(
            app(IstDevelopmentEnvironmentGuard::class),
            app(IstQuestionDatasetImporter::class),
        );

        $this->assertSame(113, IstQuestion::query()->count());
        $this->assertSame(435, IstQuestionOption::query()->count());
    }

    public function test_cleanup_is_dry_run_by_default_then_soft_deletes_only_owned_rows(): void
    {
        app(IstQuestionDatasetImporter::class)->import();
        $final = $this->createProtectedFinalQuestion();
        $markerOnly = $this->createProtectedFinalQuestion(98, '[DEV:ist-development] marker only', 1);
        $versionOnly = $this->createProtectedFinalQuestion(97, 'Reserved version only', 900000001);
        IstSubtest::query()->where('code', 'WA')->update(['instruction_content' => 'Protected final instruction']);

        $this->assertSame(0, Artisan::call('ist:remove-development-questions'));
        $this->assertSame(116, IstQuestion::query()->count());
        $this->assertStringContainsString('dry-run', Artisan::output());

        $this->assertSame(0, Artisan::call('ist:remove-development-questions', ['--confirm' => true]));
        $this->assertSame(3, IstQuestion::query()->count());
        $this->assertTrue($final->fresh()->exists);
        $this->assertTrue($markerOnly->fresh()->exists);
        $this->assertTrue($versionOnly->fresh()->exists);
        $this->assertSame(113, IstQuestion::onlyTrashed()->count());
        $this->assertSame(0, IstQuestionOption::query()->count());
        $this->assertSame(0, IstSubtest::query()->where('instruction_content', 'like', '[DEV:ist-development]%')->count());
        $this->assertSame('Protected final instruction', IstSubtest::query()->where('code', 'WA')->value('instruction_content'));
    }

    public function test_cleanup_rejects_active_sessions_without_mutation(): void
    {
        app(IstQuestionDatasetImporter::class)->import();
        IstTest::query()->create([
            'public_id' => '00000000-0000-4000-8000-000000000002',
            'participant_name' => 'DEV participant',
            'age' => 20,
            'gender' => 'P',
            'status' => IstTest::STATUS_IN_PROGRESS,
            'current_subtest_sequence' => 1,
        ]);

        $this->expectException(InvalidIstQuestionDatasetException::class);
        Artisan::call('ist:remove-development-questions', ['--confirm' => true]);
    }

    private function createProtectedFinalQuestion(
        int $questionNumber = 99,
        string $prompt = 'Protected final content',
        int $version = 1,
    ): IstQuestion {
        $subtest = IstSubtest::query()->where('code', 'SE')->firstOrFail();

        return IstQuestion::query()->create([
            'ist_subtest_id' => $subtest->id,
            'question_number' => $questionNumber,
            'display_order' => $questionNumber,
            'kind' => IstQuestion::KIND_SCORED,
            'answer_type' => 'single_choice',
            'prompt' => $prompt,
            'max_score' => 1,
            'version' => $version,
            'is_active' => true,
        ]);
    }
}
