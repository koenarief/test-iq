<?php

namespace App\Http\Controllers\Ist;

use App\Exceptions\Ist\IstResultUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\Ist\IstParticipantPayloadPresenter;
use App\Models\Ist\IstTest;
use App\Services\Ist\IstResultService;
use Inertia\Inertia;
use Inertia\Response;

final class IstResultController extends Controller
{
    public function show(
        IstTest $test,
        IstResultService $results,
        IstParticipantPayloadPresenter $presenter,
    ): Response {
        try {
            $result = $results->build($test);
        } catch (IstResultUnavailableException) {
            abort(409, 'IST result is not available.');
        }

        return Inertia::render('IST/Result', $presenter->result($result));
    }
}
