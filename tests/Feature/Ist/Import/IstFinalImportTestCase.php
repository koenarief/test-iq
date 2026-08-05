<?php

namespace Tests\Feature\Ist\Import;

use App\Models\Ist\IstSubtest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ist\Services\IstDatabaseTestCase;
use Tests\Fixtures\Ist\FinalDatasetFactory;

abstract class IstFinalImportTestCase extends IstDatabaseTestCase
{
    protected string $datasetDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        // Fixture normalization is enclosed by IstDatabaseTestCase's outer
        // transaction and is rolled back in tearDown.
        DB::table('ist_tests')->delete();
        DB::table('ist_question_options')->delete();
        DB::table('ist_questions')->delete();
        IstSubtest::query()->update([
            'instruction_content' => null,
            'memorization_content' => null,
        ]);

        Storage::fake('ist-final-feature');
        config()->set('ist.final_media_disk', 'ist-final-feature');
        config()->set('ist.final_database_allowlist', ['tes_iq_testing']);
        $this->datasetDirectory = FinalDatasetFactory::create();
    }

    protected function tearDown(): void
    {
        FinalDatasetFactory::remove($this->datasetDirectory);

        parent::tearDown();
    }
}
