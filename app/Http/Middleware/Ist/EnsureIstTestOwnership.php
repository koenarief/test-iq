<?php

namespace App\Http\Middleware\Ist;

use App\Models\Ist\IstTest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIstTestOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $test = $request->route('test');

        if (! $test instanceof IstTest) {
            abort(404);
        }

        $token = $request->session()->get("ist.access_tokens.{$test->public_id}");

        if (! is_string($token)
            || ! is_string($test->access_token_hash)
            || ! hash_equals($test->access_token_hash, hash('sha256', $token))) {
            abort(404);
        }

        return $next($request);
    }
}
