<?php

namespace App\Http\Middleware\Ist;

use App\Models\Ist\IstQuestion;
use App\Services\Ist\Import\Final\IstRehearsalPreviewGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnsureIstRehearsalPreviewAccess
{
    public function __construct(
        private readonly IstRehearsalPreviewGuard $guard,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('ist.rehearsal_preview_enabled', false)) {
            return $next($request);
        }

        if (! $request->user()) {
            abort(404);
        }

        $version = (int) config('ist.rehearsal_preview_record_version', 0);

        try {
            $this->guard->inspect($version);

            $questions = IstQuestion::query()
                ->where('version', $version)
                ->whereNull('deleted_at')
                ->where('is_active', true);
            $questionIds = (clone $questions)->pluck('id');
            $scored = (clone $questions)->where('kind', IstQuestion::KIND_SCORED)->count();
            $examples = (clone $questions)->where('kind', IstQuestion::KIND_EXAMPLE)->count();
            $options = DB::table('ist_question_options')
                ->whereIn('ist_question_id', $questionIds)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->count();
            $foreignActive = IstQuestion::query()
                ->where('version', '!=', $version)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->exists();

            if ($questionIds->count() !== 113
                || $scored !== 104
                || $examples !== 9
                || $options !== 435
                || $foreignActive) {
                abort(404);
            }
        } catch (Throwable) {
            abort(404);
        }

        return $next($request);
    }
}
