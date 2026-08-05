<?php

namespace App\Http\Controllers\Ist;

use App\Enums\Ist\IstAccessDestination;
use App\Enums\Ist\IstFinalizationReason;
use App\Exceptions\Ist\InvalidIstFinalizationException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\Ist\IstParticipantPayloadPresenter;
use App\Http\Requests\Ist\FinalizeIstSubtestRequest;
use App\Http\Requests\Ist\StartIstSubtestRequest;
use App\Http\Support\Ist\IstCanonicalNavigator;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstSubtestFinalizationService;
use App\Services\Ist\IstSubtestStartService;
use App\Services\Ist\IstTimerService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class IstSubtestSessionController extends Controller
{
    public function start(
        StartIstSubtestRequest $request,
        IstTest $test,
        string $subtest,
        IstSubtestStartService $starter,
        IstCanonicalNavigator $navigator,
    ): RedirectResponse {
        $subtest = $request->attributes->get('ist_runtime_subtest');

        if (! $subtest instanceof IstTestSubtest) {
            abort(404);
        }

        $now = CarbonImmutable::now();

        try {
            $starter->start($subtest, $now);
            $decision = $navigator->decide(
                $test->fresh(),
                IstAccessDestination::WORK,
                $subtest->fresh(),
                $now,
            );
        } catch (DomainException) {
            abort(409, 'Subtes belum dapat dimulai.');
        }

        return $navigator->redirect($test, $decision);
    }

    public function work(
        \Illuminate\Http\Request $request,
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
                IstAccessDestination::WORK,
                $subtest,
                $now,
            );
        } catch (DomainException) {
            abort(409, 'Status sesi asesmen tidak valid.');
        }

        $workDestinations = [
            IstAccessDestination::MEMORIZATION,
            IstAccessDestination::WORK,
            IstAccessDestination::EXPIRED,
        ];

        if ((int) $decision->canonicalTestSubtestId !== (int) $subtest->id
            || ! in_array($decision->destination, $workDestinations, true)) {
            return $navigator->redirect($test, $decision);
        }

        return Inertia::render('IST/Work', $presenter->work(
            $test,
            $subtest,
            $decision,
            $now,
        ));
    }

    public function finish(
        FinalizeIstSubtestRequest $request,
        IstTest $test,
        string $subtest,
        IstSubtestFinalizationService $finalizer,
        IstTimerService $timer,
        IstCanonicalNavigator $navigator,
    ): RedirectResponse {
        $subtest = $request->attributes->get('ist_runtime_subtest');

        if (! $subtest instanceof IstTestSubtest) {
            abort(404);
        }

        $now = CarbonImmutable::now();
        $reason = $request->reason();

        try {
            $finalizer->finalize(
                $subtest,
                $reason,
                $now,
                $request->finalAnswers(),
            );
        } catch (InvalidIstFinalizationException $exception) {
            $freshRuntime = $subtest->fresh();

            if ($reason !== IstFinalizationReason::SUBMITTED
                || ! $freshRuntime
                || ! $timer->isExpired($freshRuntime, $now)) {
                abort(409, 'Subtes belum dapat diselesaikan.');
            }

            try {
                $finalizer->finalize(
                    $freshRuntime,
                    IstFinalizationReason::TIMEOUT,
                    $now,
                );
            } catch (DomainException) {
                abort(409, 'Batas waktu subtes belum dapat diselesaikan.');
            }
        } catch (DomainException) {
            abort(409, 'Subtes belum dapat diselesaikan.');
        }

        try {
            $freshTest = $test->fresh();
            $decision = $navigator->decide(
                $freshTest,
                IstAccessDestination::INSTRUCTION,
                null,
                $now,
            );
        } catch (DomainException) {
            abort(409, 'Tujuan asesmen belum tersedia.');
        }

        return $navigator->redirect($freshTest, $decision);
    }
}
