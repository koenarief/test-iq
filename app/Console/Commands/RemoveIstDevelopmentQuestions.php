<?php

namespace App\Console\Commands;

use App\Exceptions\Ist\InvalidIstQuestionDatasetException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTest;
use App\Services\Ist\Import\IstDevelopmentEnvironmentGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class RemoveIstDevelopmentQuestions extends Command
{
    protected $signature = 'ist:remove-development-questions
                            {--confirm : Perform soft-delete instead of dry-run}';

    protected $description = 'Dry-run or safely soft-delete only owned IST development questions';

    public function handle(IstDevelopmentEnvironmentGuard $guard): int
    {
        $context = $guard->assertSafe();
        $this->assertNoActiveTests();
        $questionIds = $this->ownedQuestionQuery()->pluck('id');
        $questionCount = $questionIds->count();
        $optionCount = IstQuestionOption::query()->whereIn('ist_question_id', $questionIds)->count();
        $contentCount = IstSubtest::query()
            ->where(fn ($query) => $query
                ->where('instruction_content', 'like', config('ist.development_marker').'%')
                ->orWhere('memorization_content', 'like', config('ist.development_marker').'%'))
            ->count();

        $mode = $this->option('confirm') ? 'actual' : 'dry-run';
        $this->line(sprintf(
            'IST DEV cleanup %s on %s/%s: questions=%d, options=%d, subtest_contents=%d, media_deleted=0.',
            $mode,
            $context->environment,
            $context->activeDatabase,
            $questionCount,
            $optionCount,
            $contentCount,
        ));

        if (! $this->option('confirm')) {
            $this->warn('Dry-run only. Use --confirm to mutate eligible development rows.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $this->assertNoActiveTests(true);
            $ids = $this->ownedQuestionQuery(true)->pluck('id');
            IstQuestionOption::query()->whereIn('ist_question_id', $ids)->delete();
            IstQuestion::query()->whereIn('id', $ids)->delete();

            $marker = (string) config('ist.development_marker');
            IstSubtest::query()->lockForUpdate()->get()->each(function (IstSubtest $subtest) use ($marker): void {
                $changed = false;
                foreach (['instruction_content', 'memorization_content'] as $column) {
                    if (is_string($subtest->{$column}) && str_starts_with($subtest->{$column}, $marker)) {
                        $subtest->{$column} = null;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $subtest->save();
                }
            });
        }, 3);

        $this->info('Owned IST development rows were soft-deleted; media and runtime data were not changed.');

        return self::SUCCESS;
    }

    private function ownedQuestionQuery(bool $lock = false)
    {
        $query = IstQuestion::query()
            ->whereBetween('version', [
                (int) config('ist.development_version_min'),
                (int) config('ist.development_version_max'),
            ])
            ->where('prompt', 'like', config('ist.development_marker').'%');

        return $lock ? $query->lockForUpdate() : $query;
    }

    private function assertNoActiveTests(bool $lock = false): void
    {
        $query = IstTest::query()->whereIn('status', [IstTest::STATUS_DRAFT, IstTest::STATUS_IN_PROGRESS]);
        if ($lock) {
            $query->lockForUpdate();
        }
        if ($query->exists()) {
            throw InvalidIstQuestionDatasetException::because('cleanup ditolak karena terdapat test IST draft/in_progress');
        }
    }
}
