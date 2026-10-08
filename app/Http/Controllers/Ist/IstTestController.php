<?php

namespace App\Http\Controllers\Ist;

use App\Enums\Ist\IstAccessDestination;
use App\Http\Controllers\Controller;
use App\Http\Support\Ist\IstCanonicalNavigator;
use App\Models\Ist\IstTest;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class IstTestController extends Controller
{
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
