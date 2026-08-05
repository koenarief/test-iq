<?php

namespace App\Http\Controllers\Ist;

use App\Exceptions\Ist\InvalidIstAutosaveException;
use App\Exceptions\Ist\IstAnswerRevisionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ist\AutosaveIstAnswerRequest;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstAutosaveService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class IstAnswerController extends Controller
{
    public function update(
        AutosaveIstAnswerRequest $request,
        IstTest $test,
        string $subtest,
        IstAutosaveService $autosave,
    ): JsonResponse {
        $subtest = $request->attributes->get('ist_runtime_subtest');

        if (! $subtest instanceof IstTestSubtest) {
            abort(404);
        }

        $now = CarbonImmutable::now();

        try {
            $result = $autosave->save($subtest, $request->changes(), $now);
        } catch (IstAnswerRevisionConflictException) {
            return response()->json([
                'message' => 'Answer revision conflicts with a newer payload.',
                'code' => 'IST_ANSWER_REVISION_CONFLICT',
            ], 409);
        } catch (InvalidIstAutosaveException) {
            return response()->json([
                'message' => 'Subtest cannot accept answers.',
                'code' => 'IST_SUBTEST_STATE_CONFLICT',
            ], 409);
        }

        return response()->json([
            'savedQuestionIds' => $result->savedQuestionIds,
            'ignoredStaleQuestionIds' => $result->ignoredStaleQuestionIds,
            'idempotentQuestionIds' => $result->idempotentQuestionIds,
            'savedAt' => $result->savedAt->toISOString(),
            'remainingSeconds' => $result->remainingSeconds,
        ]);
    }
}
