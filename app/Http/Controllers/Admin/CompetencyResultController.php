<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetencyTest;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyResultController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: null;
        $search = $request->string('search')->value() ?: null;
        $department = $request->string('department')->value() ?: null;
        $merchantId = $request->filled('merchant_id') ? $request->integer('merchant_id') : null;

        $tests = CompetencyTest::query()
            ->with(['merchant:id,name'])
            ->select([
                'id', 'participant_name', 'age', 'gender', 'department', 'status',
                'total_score', 'started_at', 'finished_at', 'created_at', 'merchant_id',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($department, fn ($query) => $query->where('department', $department))
            ->when($search, fn ($query) => $query->where('participant_name', 'like', '%'.$search.'%'))
            ->when($merchantId !== null, fn ($query) => $merchantId === 0
                ? $query->whereNull('merchant_id')
                : $query->where('merchant_id', $merchantId))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $tests->getCollection()->transform(fn ($test) => [
            ...$test->toArray(),
            'department_label' => config("competency.departments.{$test->department}.label", $test->department),
        ]);

        return Inertia::render('Admin/CompetencyResults/Index', [
            'tests' => $tests,
            'statuses' => ['draft', 'in_progress', 'completed'],
            'departments' => $this->departmentOptions(),
            'merchants' => Merchant::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'department' => $department,
                'merchant_id' => $merchantId,
            ],
        ]);
    }

    public function show(CompetencyTest $competencyTest): Response
    {
        $competencyTest->load('merchant:id,name');

        $breakdown = $this->categoryBreakdown($competencyTest);

        return Inertia::render('Admin/CompetencyResults/Show', [
            'test' => [
                ...$competencyTest->only([
                    'id', 'participant_name', 'age', 'gender', 'department',
                    'status', 'started_at', 'finished_at', 'total_score',
                ]),
                'department_label' => config("competency.departments.{$competencyTest->department}.label", $competencyTest->department),
                'merchant_name' => $competencyTest->merchant?->name,
            ],
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function departmentOptions(): array
    {
        return collect(config('competency.departments'))
            ->map(fn ($department, $key) => ['key' => $key, 'label' => $department['label']])
            ->values()
            ->all();
    }

    private function categoryBreakdown(CompetencyTest $competencyTest)
    {
        return $competencyTest->answers()
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
    }
}
