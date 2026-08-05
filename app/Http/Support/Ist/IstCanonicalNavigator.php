<?php

namespace App\Http\Support\Ist;

use App\Data\Ist\IstSubtestAccessDecision;
use App\Enums\Ist\IstAccessDestination;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstSubtestAccessService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;

final class IstCanonicalNavigator
{
    public function __construct(
        private readonly IstSubtestAccessService $access,
    ) {}

    public function decide(
        IstTest $test,
        IstAccessDestination $requestedDestination,
        ?IstTestSubtest $requestedSubtest,
        CarbonInterface $now,
    ): IstSubtestAccessDecision {
        return $this->access->decide($test, $requestedDestination, $requestedSubtest, $now);
    }

    public function redirect(IstTest $test, IstSubtestAccessDecision $decision): RedirectResponse
    {
        if ($decision->destination === IstAccessDestination::RESULT) {
            return redirect()->route('ist.result', ['test' => $test->public_id], 303);
        }

        if ($decision->destination === IstAccessDestination::UNAVAILABLE) {
            abort(404);
        }

        if ($decision->destination === IstAccessDestination::COMPLETED) {
            abort(409, 'IST completed runtime was not advanced.');
        }

        $runtime = $decision->canonicalTestSubtestId === null
            ? null
            : IstTestSubtest::query()
                ->with('subtest')
                ->where('ist_test_id', $test->id)
                ->find($decision->canonicalTestSubtestId);

        if (! $runtime || ! $runtime->subtest) {
            abort(409, 'IST canonical runtime is unavailable.');
        }

        $route = $decision->destination === IstAccessDestination::INSTRUCTION
            ? 'ist.subtests.instruction'
            : 'ist.subtests.work';

        return redirect()->route($route, [
            'test' => $test->public_id,
            'subtest' => $runtime->subtest->code,
        ], 303);
    }
}
