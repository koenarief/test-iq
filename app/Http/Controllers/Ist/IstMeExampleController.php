<?php

namespace App\Http\Controllers\Ist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ist\CompleteIstMeExampleRequest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstMeExampleCompletionService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class IstMeExampleController extends Controller
{
    public function complete(
        CompleteIstMeExampleRequest $request,
        IstTest $test,
        string $subtest,
        IstMeExampleCompletionService $completion,
    ): RedirectResponse {
        $runtime = $request->attributes->get('ist_runtime_subtest');

        if (! $runtime instanceof IstTestSubtest || $runtime->subtest?->code !== 'ME') {
            abort(404);
        }

        try {
            $completion->complete(
                $runtime,
                $request->string('selected_option_key')->toString(),
                CarbonImmutable::now(),
            );
        } catch (DomainException) {
            abort(409, 'Contoh ME belum dapat diselesaikan.');
        }

        return redirect()->route('ist.subtests.instruction', [
            'test' => $test->public_id,
            'subtest' => 'ME',
        ], 303);
    }
}
