<?php

namespace App\Http\Controllers\Ist;

use App\Actions\Ist\CreateIstParticipantSession;
use App\Enums\Ist\IstAccessDestination;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ist\StartIstTestRequest;
use App\Http\Support\Ist\IstCanonicalNavigator;
use App\Models\Ist\IstTest;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class IstTestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('IST/Biodata');
    }

    public function store(
        StartIstTestRequest $request,
        CreateIstParticipantSession $creator,
    ): RedirectResponse {
        try {
            $creation = $creator->create($request->participantData());
        } catch (DomainException) {
            abort(409, 'Asesmen belum tersedia.');
        }

        $rawToken = $creation->takeRawAccessToken();
        $request->session()->regenerate();
        $request->session()->put(
            "ist.access_tokens.{$creation->test->public_id}",
            $rawToken,
        );
        unset($rawToken);

        return redirect()->route('ist.resume', [
            'test' => $creation->test->public_id,
        ], 303);
    }

    public function resume(
        IstTest $test,
        IstCanonicalNavigator $navigator,
    ): RedirectResponse {
        $now = CarbonImmutable::now();

        try {
            $decision = $navigator->decide(
                $test,
                IstAccessDestination::INSTRUCTION,
                null,
                $now,
            );
        } catch (DomainException) {
            abort(409, 'Status sesi asesmen tidak valid.');
        }

        return $navigator->redirect($test, $decision);
    }
}
