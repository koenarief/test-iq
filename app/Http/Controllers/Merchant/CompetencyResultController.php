<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\CompetencyTest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyResultController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: null;
        $search = $request->string('search')->value() ?: null;

        $tests = CompetencyTest::query()
            ->select([
                'id', 'participant_name', 'age', 'gender', 'department', 'status',
                'total_score', 'started_at', 'finished_at', 'created_at', 'merchant_id',
            ])
            ->where('merchant_id', $request->user()->merchant_id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where('participant_name', 'like', '%'.$search.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $tests->getCollection()->transform(fn ($test) => [
            ...$test->toArray(),
            'department_label' => config("competency.departments.{$test->department}.label", $test->department),
        ]);

        return Inertia::render('Merchant/CompetencyResults/Index', [
            'tests' => $tests,
            'statuses' => ['draft', 'in_progress', 'completed'],
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, CompetencyTest $competencyTest): Response
    {
        abort_unless($competencyTest->merchant_id === $request->user()->merchant_id, 404);

        $breakdown = $competencyTest->answers()
            ->join('competency_categories', 'competency_categories.id', '=', 'competency_answers.competency_category_id')
            ->selectRaw('
                competency_categories.id as category_id,
                competency_categories.name as category_name,
                COUNT(*) as answered_count,
                AVG(competency_answers.points) as avg_points
            ')
            ->groupBy('competency_categories.id', 'competency_categories.name', 'competency_categories.order')
            ->orderBy('competency_categories.order')
            ->get()
            ->map(fn ($row) => [
                'category_id' => $row->category_id,
                'category_name' => $row->category_name,
                'answered_count' => (int) $row->answered_count,
                'avg_points' => round((float) $row->avg_points, 2),
                'percentage' => round(((float) $row->avg_points / 5) * 100, 1),
            ]);

        return Inertia::render('Merchant/CompetencyResults/Show', [
            'test' => [
                ...$competencyTest->only([
                    'id', 'participant_name', 'age', 'gender', 'department',
                    'status', 'started_at', 'finished_at', 'total_score',
                ]),
                'department_label' => config("competency.departments.{$competencyTest->department}.label", $competencyTest->department),
            ],
            'breakdown' => $breakdown,
        ]);
    }
}
