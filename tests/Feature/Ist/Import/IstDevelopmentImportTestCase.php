<?php

namespace Tests\Feature\Ist\Import;

use App\Models\Ist\IstSubtest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Ist\Services\IstDatabaseTestCase;

abstract class IstDevelopmentImportTestCase extends IstDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // This cleanup is enclosed by IstDatabaseTestCase's outer transaction.
        // It only normalizes the test fixture and is fully rolled back in tearDown.
        DB::table('ist_tests')->delete();
        DB::table('ist_question_options')->delete();
        DB::table('ist_questions')->delete();
        IstSubtest::query()->update([
            'instruction_content' => null,
            'memorization_content' => null,
        ]);

        Storage::fake('ist-development-feature');
        config()->set('ist.development_media_disk', 'ist-development-feature');
    }
}
