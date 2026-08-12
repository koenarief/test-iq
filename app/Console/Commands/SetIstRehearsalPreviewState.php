<?php

namespace App\Console\Commands;

use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstTest;
use App\Services\Ist\Import\Final\IstRehearsalPreviewGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SetIstRehearsalPreviewState extends Command
{
    protected $signature = 'ist:rehearsal-preview
        {version : Record version final yang sudah diimpor pada clone}
        {--allow-database= : Nama database clone eksplisit}
        {--enable : Aktifkan hanya version rehearsal pada clone}
        {--disable : Nonaktifkan kembali hanya version rehearsal pada clone}
        {--confirm : Konfirmasi eksplisit perubahan state clone}';

    protected $description = 'Aktivasi terkontrol dataset IST hanya untuk clone rehearsal/UAT';

    public function handle(IstRehearsalPreviewGuard $guard): int
    {
        $version = filter_var($this->argument('version'), FILTER_VALIDATE_INT);
        $explicitDatabase = $this->option('allow-database');
        $explicitDatabase = is_string($explicitDatabase) && trim($explicitDatabase) !== ''
            ? trim($explicitDatabase)
            : null;
        $enable = (bool) $this->option('enable');
        $disable = (bool) $this->option('disable');
        $confirm = (bool) $this->option('confirm');

        if (! is_int($version) || $enable === $disable) {
            $this->components->error('Version harus integer dan pilih tepat satu dari --enable/--disable.');

            return self::FAILURE;
        }

        try {
            $context = $guard->inspect($version, $explicitDatabase);
            $counts = $this->datasetCounts($version);

            $this->table(['Field', 'Value'], [
                ['Environment', $context['environment']],
                ['Connection', $context['connection']],
                ['Host', $context['host']],
                ['Configured database', $context['configured_database']],
                ['Active database', $context['active_database']],
                ['Record version', (string) $version],
                ['Questions', (string) $counts['questions']],
                ['Scored', (string) $counts['scored']],
                ['Examples', (string) $counts['examples']],
                ['Options', (string) $counts['options']],
                ['Requested state', $enable ? 'ACTIVE-REHEARSAL' : 'INACTIVE'],
                ['Mode', $confirm ? 'WRITE-CLONE' : 'VALIDATE-ONLY'],
            ]);

            if (! $confirm) {
                $this->components->info('Validasi selesai tanpa perubahan database.');

                return self::SUCCESS;
            }

            if ($explicitDatabase === null) {
                $this->components->error('--confirm memerlukan --allow-database yang sama dengan clone aktif.');

                return self::FAILURE;
            }

            DB::transaction(function () use ($version, $enable): void {
                if (IstTest::query()->whereIn('status', [
                    IstTest::STATUS_DRAFT,
                    IstTest::STATUS_IN_PROGRESS,
                ])->lockForUpdate()->exists()) {
                    throw new \DomainException('Terdapat sesi IST aktif pada clone.');
                }

                $questionIds = IstQuestion::query()
                    ->where('version', $version)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->pluck('id');

                if ($questionIds->count() !== 113) {
                    throw new \DomainException('Dataset rehearsal harus mempunyai tepat 113 record question.');
                }

                $scoredCount = IstQuestion::query()
                    ->whereIn('id', $questionIds)
                    ->where('kind', IstQuestion::KIND_SCORED)
                    ->count();
                $exampleCount = IstQuestion::query()
                    ->whereIn('id', $questionIds)
                    ->where('kind', IstQuestion::KIND_EXAMPLE)
                    ->count();
                $optionIds = DB::table('ist_question_options')
                    ->whereIn('ist_question_id', $questionIds)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->pluck('id');

                if ($scoredCount !== 104 || $exampleCount !== 9 || $optionIds->count() !== 435) {
                    throw new \DomainException('Dataset rehearsal tidak memenuhi kontrak 104/9/435.');
                }

                if ($enable && IstQuestion::query()
                    ->where('version', '!=', $version)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->exists()) {
                    throw new \DomainException('Terdapat question aktif dari version lain.');
                }

                DB::table('ist_question_options')
                    ->whereIn('ist_question_id', $questionIds)
                    ->whereNull('deleted_at')
                    ->update(['is_active' => $enable]);
                DB::table('ist_questions')
                    ->whereIn('id', $questionIds)
                    ->update(['is_active' => $enable]);
            }, 3);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info($enable
            ? 'Dataset aktif hanya untuk rehearsal clone yang terlindungi.'
            : 'Dataset rehearsal telah kembali inactive.');

        return self::SUCCESS;
    }

    private function datasetCounts(int $version): array
    {
        $questions = IstQuestion::query()
            ->where('version', $version)
            ->whereNull('deleted_at');
        $questionIds = (clone $questions)->pluck('id');

        return [
            'questions' => (clone $questions)->count(),
            'scored' => (clone $questions)->where('kind', IstQuestion::KIND_SCORED)->count(),
            'examples' => (clone $questions)->where('kind', IstQuestion::KIND_EXAMPLE)->count(),
            'options' => DB::table('ist_question_options')
                ->whereIn('ist_question_id', $questionIds)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }
}
