<?php

namespace App\Http\Controllers\Ist;

use App\Actions\Ist\CreateIstParticipantSession;
use App\Enums\Ist\IstAccessDestination;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ist\StartIstTestRequest;
use App\Http\Support\Ist\IstCanonicalNavigator;
use App\Models\Ist\IstTest;
use App\Models\Merchant;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

final class IstTestController extends Controller
{
    private const SESSION_MERCHANT_KEY = 'ist.merchant_id';

    public function index(Request $request, ?Merchant $merchant = null): Response
    {
        if ($merchant !== null && ! $merchant->is_active) {
            abort(404);
        }

        $request->session()->put(self::SESSION_MERCHANT_KEY, $merchant?->id);

        return Inertia::render('IST/Biodata', [
            'merchantName' => $merchant?->name,
        ]);
    }

    public function store(
        StartIstTestRequest $request,
        CreateIstParticipantSession $creator,
    ): RedirectResponse {
        $merchantId = $request->session()->get(self::SESSION_MERCHANT_KEY);

        if ($merchantId !== null && ! Merchant::where('id', $merchantId)->where('is_active', true)->exists()) {
            $merchantId = null;
        }

        try {
            $creation = $creator->create($request->participantData(), $merchantId);
        } catch (DomainException $exception) {
            Log::warning('IST participant session creation was rejected.', [
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);

            abort(409, 'Asesmen belum tersedia.');
        }

        $rawToken = $creation->takeRawAccessToken();
        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_MERCHANT_KEY);
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
