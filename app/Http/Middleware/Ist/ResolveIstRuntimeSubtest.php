<?php

namespace App\Http\Middleware\Ist;

use App\Models\Ist\IstTest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveIstRuntimeSubtest
{
    public function handle(Request $request, Closure $next): Response
    {
        $test = $request->route('test');
        $code = $request->route('subtest');

        if (! $test instanceof IstTest || ! is_string($code)) {
            abort(404);
        }

        $runtime = $test->subtests()
            ->whereHas('subtest', fn ($query) => $query->where('code', $code))
            ->with('subtest')
            ->first();

        if (! $runtime) {
            abort(404);
        }

        $request->attributes->set('ist_runtime_subtest', $runtime);

        return $next($request);
    }
}
