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
        {--staging : Validasi paket human-reviewed yang belum approved/frozen; selalu read-only}
        {--allow-database= : Nama database eksplisit untuk mengizinkan operasi tulis}
        {--confirm-write : Konfirmasi eksplisit operasi tulis inactive setelah seluruh guard lulus}
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
        $staging = (bool) $this->option('staging');
        $confirmWrite = (bool) $this->option('confirm-write');
        $dryRun = (bool) $this->option('dry-run') || ! $confirmWrite;

        if ((bool) $this->option('dry-run') && $confirmWrite) {
            $this->components->error('--dry-run dan --confirm-write tidak boleh digunakan bersamaan.');

            return self::FAILURE;
        }

        if ($staging) {
        try {
            $validated = $validator->validateStaging($path);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Final staging dataset validation');
        $this->table(
            ['Field', 'Value'],
            [
                ['Instrument', $validated->manifest['instrument_identifier']],
                ['Question bank version', $validated->manifest['question_bank_version']],
                ['Scored questions', (string) $validated->scoredQuestionCount],
                ['Examples', (string) $validated->exampleQuestionCount],
                ['Options', (string) $validated->optionCount],
                ['Media', (string) count($validated->media)],
                ['Review status', $validated->manifest['review_status']],
                ['Approved', $validated->manifest['approved'] ? 'true' : 'false'],
                ['Frozen', $validated->manifest['frozen'] ? 'true' : 'false'],
                ['Active', $validated->manifest['active'] ? 'true' : 'false'],
                ['Imported', $validated->manifest['imported'] ? 'true' : 'false'],
            ],
        );

        if ($dryRun) {
            $this->components->info('Validasi staging selesai tanpa perubahan database/media.');

            return self::SUCCESS;
        }

        try {
            $result = $importer->import(
                $path,
                $allowDatabase,
                false,
                true,
            );
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

        $this->components->info('Import staging selesai dalam keadaan inactive.');

        return self::SUCCESS;
    }

        try {
            $validated = $validator->validate($path);
            $testFixture = ($validated->manifest['test_fixture'] ?? false) === true;
            $context = $guard->inspect($allowDatabase, ! $dryRun, $testFixture);
            $expectedVersion = $this->option('question-bank-version');

            if (is_string($expectedVersion) && trim($expectedVersion) !== ''
                && trim($expectedVersion) !== $validated->manifest['question_bank_version']) {
                $this->components->error('Question bank version berbeda dari --question-bank-version.');

                return self::FAILURE;
            }

            $this->components->info('Final dataset pipeline');
            $this->line('Environment: '.$context->environment);
            $this->line('Connection: '.$context->connection);
            $this->line('Host: '.$context->host);
            $this->line('Configured database: '.$context->configuredDatabase);
            $this->line('Active database: '.$context->activeDatabase);
            $this->line('Instrument: '.$validated->manifest['instrument_identifier']);
            $this->line('Version: '.$validated->manifest['question_bank_version']);
            $this->line('Fingerprint: '.($validated->manifest['content_fingerprint'] ?? 'test-fixture'));
            $this->line('Approved: '.(($validated->manifest['approved'] ?? true) ? 'true' : 'false'));
            $this->line('Frozen: '.(($validated->manifest['freeze']['frozen'] ?? true) ? 'true' : 'false'));
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
