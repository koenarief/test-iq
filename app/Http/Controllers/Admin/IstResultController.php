<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Ist\IstResultUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\Ist\IstParticipantPayloadPresenter;
use App\Models\Ist\IstTest;
use App\Models\Merchant;
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
        $merchantId = $request->filled('merchant_id') ? $request->integer('merchant_id') : null;

        $tests = IstTest::query()
            ->select([
                'id', 'public_id', 'participant_name', 'age', 'gender',
                'status', 'current_subtest_sequence', 'started_at', 'finished_at', 'created_at',
                'merchant_id',
            ])
            ->with('merchant:id,name')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where('participant_name', 'like', '%'.$search.'%'))
            ->when($merchantId !== null, fn ($query) => $merchantId === 0
                ? $query->whereNull('merchant_id')
                : $query->where('merchant_id', $merchantId))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/IstResults/Index', [
            'tests' => $tests,
            'statuses' => [
                IstTest::STATUS_DRAFT,
                IstTest::STATUS_IN_PROGRESS,
                IstTest::STATUS_COMPLETED,
                IstTest::STATUS_CANCELLED,
            ],
            'merchants' => Merchant::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'merchant_id' => $merchantId,
            ],
        ]);
    }

    public function show(
        IstTest $test,
        IstResultService $results,
        IstParticipantPayloadPresenter $presenter,
    ): Response {
        $result = null;
        $unavailableReason = null;

        try {
            $result = $presenter->result($results->build($test));
        } catch (IstResultUnavailableException $exception) {
            $unavailableReason = $exception->getMessage();
        }

        $test->loadMissing('merchant:id,name');

        return Inertia::render('Admin/IstResults/Show', [
            'test' => [
                ...$test->only([
                    'public_id', 'participant_name', 'age', 'gender',
                    'status', 'started_at', 'finished_at',
                ]),
                'merchant_name' => $test->merchant?->name,
            ],
            'result' => $result,
            'unavailableReason' => $unavailableReason,
        ]);
    }
}
