<?php

namespace App\Http\Controllers\Merchant;

use App\Exceptions\Ist\IstResultUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\Ist\IstParticipantPayloadPresenter;
use App\Models\Ist\IstTest;
use App\Services\Ist\IstResultService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IstResultController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: null;
        $search = $request->string('search')->value() ?: null;

        $tests = IstTest::query()
            ->select([
                'id', 'public_id', 'participant_name', 'age', 'gender',
                'status', 'current_subtest_sequence', 'started_at', 'finished_at', 'created_at',
                'merchant_id',
            ])
            ->where('merchant_id', $request->user()->merchant_id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where('participant_name', 'like', '%'.$search.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Merchant/IstResults/Index', [
            'tests' => $tests,
            'statuses' => [
                IstTest::STATUS_DRAFT,
                IstTest::STATUS_IN_PROGRESS,
                IstTest::STATUS_COMPLETED,
                IstTest::STATUS_CANCELLED,
            ],
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function show(
        Request $request,
        IstTest $test,
        IstResultService $results,
        IstParticipantPayloadPresenter $presenter,
    ): Response {
        abort_unless($test->merchant_id === $request->user()->merchant_id, 404);

        $result = null;
        $unavailableReason = null;

        try {
            $result = $presenter->result($results->build($test));
        } catch (IstResultUnavailableException $exception) {
            $unavailableReason = $exception->getMessage();
        }

        return Inertia::render('Merchant/IstResults/Show', [
            'test' => $test->only([
                'public_id', 'participant_name', 'age', 'gender',
                'status', 'started_at', 'finished_at',
            ]),
            'result' => $result,
            'unavailableReason' => $unavailableReason,
        ]);
    }
}
