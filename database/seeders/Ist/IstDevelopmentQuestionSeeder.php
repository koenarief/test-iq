<?php

namespace Database\Seeders\Ist;

use App\Services\Ist\Import\IstDevelopmentEnvironmentGuard;
use App\Services\Ist\Import\IstQuestionDatasetImporter;
use Illuminate\Database\Seeder;

final class IstDevelopmentQuestionSeeder extends Seeder
{
    public function run(
        IstDevelopmentEnvironmentGuard $guard,
        IstQuestionDatasetImporter $importer,
    ): void {
        $context = $guard->assertSafe();
        $result = $importer->import();

        $this->command?->info(sprintf(
            'IST DEV imported on %s/%s: %d scored, %d examples, %d options, %d media.',
            $context->environment,
            $context->activeDatabase,
            $result->scoredQuestionCount,
            $result->exampleQuestionCount,
            $result->optionCount,
            $result->mediaCount,
        ));
    }
}
