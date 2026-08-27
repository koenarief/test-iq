<?php

namespace App\Actions\Ist;

use App\Data\Ist\IstTestCreationResult;
use App\Services\Ist\IstQuestionSnapshotService;
use App\Services\Ist\IstTestLifecycleService;
use Illuminate\Support\Facades\DB;

final class CreateIstParticipantSession
{
    public function __construct(
        private readonly IstTestLifecycleService $lifecycle,
        private readonly IstQuestionSnapshotService $snapshots,
    ) {}

    public function create(array $participantData, ?int $merchantId = null): IstTestCreationResult
    {
        return DB::transaction(function () use ($participantData, $merchantId): IstTestCreationResult {
            $result = $this->lifecycle->create($participantData, $merchantId);

            foreach ($result->test->subtests()->orderBy('sequence')->get() as $runtime) {
                $this->snapshots->snapshot($runtime);
            }

            return $result;
        });
    }
}
