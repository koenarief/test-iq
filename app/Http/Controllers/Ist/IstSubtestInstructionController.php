<?php

namespace App\Http\Controllers\Ist;

use App\Enums\Ist\IstAccessDestination;
use App\Http\Controllers\Controller;
use App\Http\Presenters\Ist\IstParticipantPayloadPresenter;
use App\Http\Support\Ist\IstCanonicalNavigator;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class IstSubtestInstructionController extends Controller
{
    public function show(
        Request $request,
        IstTest $test,
        string $subtest,
        IstCanonicalNavigator $navigator,
        IstParticipantPayloadPresenter $presenter,
    ): Response|RedirectResponse {
        $subtest = $request->attributes->get('ist_runtime_subtest');

        if (! $subtest instanceof IstTestSubtest) {
            abort(404);
        }

        $now = CarbonImmutable::now();

        try {
            $decision = $navigator->decide(
                $test,
                IstAccessDestination::INSTRUCTION,
                $subtest,
                $now,
            );
        } catch (DomainException) {
            abort(409, 'Status sesi asesmen tidak valid.');
        }

        if ((int) $decision->canonicalTestSubtestId !== (int) $subtest->id
            || $decision->destination !== IstAccessDestination::INSTRUCTION) {
            return $navigator->redirect($test, $decision);
        }

        return Inertia::render('IST/Instruction', $presenter->instruction(
            $test,
            $subtest,
            $decision->snapshotComplete,
        ));
    }
}
