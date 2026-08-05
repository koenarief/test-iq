<?php

namespace App\Console\Commands;

use App\Services\Ist\Import\Final\IstFinalEnvironmentGuard;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetImporter;
use App\Services\Ist\Import\Final\IstFinalQuestionDatasetValidator;
use Illuminate\Console\Command;
use Throwable;

final class ImportIstFinalDataset extends Command
{
    protected $signature = 'ist:import-final-dataset
        {path : Directory dataset final yang akan divalidasi}
        {--dry-run : Validasi saja tanpa mengubah database atau media}
        {--allow-database= : Nama database eksplisit untuk mengizinkan operasi tulis}
        {--question-bank-version= : Question bank version yang wajib cocok dengan manifest}';

    protected $description = 'Validasi atau impor question bank final 104 secara inactive dan terjaga';

    public function handle(
        IstFinalEnvironmentGuard $guard,
        IstFinalQuestionDatasetValidator $validator,
        IstFinalQuestionDatasetImporter $importer,
    ): int {
        $path = (string) $this->argument('path');
        $allowDatabase = $this->option('allow-database');
        $allowDatabase = is_string($allowDatabase) && trim($allowDatabase) !== ''
            ? trim($allowDatabase)
            : null;
        $dryRun = (bool) $this->option('dry-run') || $allowDatabase === null;

        try {
            $context = $guard->inspect($allowDatabase, ! $dryRun);
            $validated = $validator->validate($path);
            $expectedVersion = $this->option('question-bank-version');

            if (is_string($expectedVersion) && trim($expectedVersion) !== ''
                && trim($expectedVersion) !== $validated->manifest['question_bank_version']) {
                $this->components->error('Question bank version berbeda dari --question-bank-version.');

                return self::FAILURE;
            }

            $this->components->info('Final dataset pipeline');
            $this->line('Environment: '.$context->environment);
            $this->line('Connection: '.$context->connection);
            $this->line('Configured database: '.$context->configuredDatabase);
            $this->line('Active database: '.$context->activeDatabase);
            $this->line('Instrument: '.$validated->manifest['instrument_identifier']);
            $this->line('Version: '.$validated->manifest['question_bank_version']);
            $this->line('Mode: '.($dryRun ? 'DRY-RUN' : 'WRITE-INACTIVE'));

            $result = $importer->import($path, $allowDatabase, $dryRun);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['Instrument', $result->instrumentIdentifier],
                ['Question bank version', $result->questionBankVersion],
                ['Scored questions', (string) $result->importedQuestions],
                ['Examples', (string) $result->importedExamples],
                ['Options', (string) $result->importedOptions],
                ['Media', (string) $result->importedMedia],
                ['Active', $result->active ? 'true' : 'false'],
            ],
        );

        foreach ($result->warnings as $warning) {
            $this->components->warn($warning);
        }

        $this->components->info($dryRun
            ? 'Dry-run selesai tanpa perubahan database atau media.'
            : 'Import selesai dalam keadaan inactive.');

        return self::SUCCESS;
    }
}
