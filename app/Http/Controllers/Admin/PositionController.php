<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetencyCategory;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->value() ?: null;

        $positions = Position::query()
            ->withCount('competencyCategoryWeights')
            ->with('competencyCategoryWeights')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $positions->getCollection()->transform(function (Position $position) {
            return [
                'id' => $position->id,
                'name' => $position->name,
                'department' => $position->department,
                'is_active' => $position->is_active,
                'total_weight' => $position->totalWeight(),
            ];
        });

        return Inertia::render('Admin/Positions/Index', [
            'positions' => $positions,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Positions/Create', [
            'departments' => $this->departmentOptions(),
            'categoriesByDepartment' => $this->categoriesByDepartment(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePosition($request);

        DB::transaction(function () use ($validated) {
            $position = Position::create([
                'name' => $validated['name'],
                'department' => $validated['department'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->syncCategoryWeights($position, $validated['category_weights']);
        });

        return redirect()
            ->route('admin.positions.index')
            ->with('success', 'Posisi berhasil ditambahkan.');
    }

    public function edit(Position $position): Response
    {
        $position->load('competencyCategoryWeights');

        return Inertia::render('Admin/Positions/Edit', [
            'position' => [
                'id' => $position->id,
                'name' => $position->name,
                'department' => $position->department,
                'is_active' => $position->is_active,
                'category_weights' => $position->competencyCategoryWeights->map(fn ($weight) => [
                    'competency_category_id' => $weight->competency_category_id,
                    'weight' => (float) $weight->weight,
                ]),
            ],
            'departments' => $this->departmentOptions(),
            'categoriesByDepartment' => $this->categoriesByDepartment(),
        ]);
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $validated = $this->validatePosition($request);

        DB::transaction(function () use ($position, $validated) {
            $position->update([
                'name' => $validated['name'],
                'department' => $validated['department'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->syncCategoryWeights($position, $validated['category_weights']);
        });

        return redirect()
            ->route('admin.positions.index')
            ->with('success', 'Posisi berhasil diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $position->delete();

        return redirect()
            ->route('admin.positions.index')
            ->with('success', 'Posisi berhasil dihapus.');
    }

    private function validatePosition(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department' => ['required', Rule::in(array_keys(config('competency.departments')))],
            'is_active' => ['boolean'],
            'category_weights' => ['required', 'array', 'min:1'],
            'category_weights.*.competency_category_id' => ['required', 'exists:competency_categories,id'],
            'category_weights.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $total = array_sum(array_column($validated['category_weights'], 'weight'));

        if (round($total, 2) !== 100.0) {
            throw ValidationException::withMessages([
                'category_weights' => "Total bobot subtes Competency (Job Fit) harus 100%, saat ini {$total}%.",
            ]);
        }

        return $validated;
    }

    private function syncCategoryWeights(Position $position, array $categoryWeights): void
    {
        $position->competencyCategoryWeights()->delete();

        foreach ($categoryWeights as $categoryWeight) {
            $position->competencyCategoryWeights()->create([
                'competency_category_id' => $categoryWeight['competency_category_id'],
                'weight' => $categoryWeight['weight'],
            ]);
        }
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

    private function categoriesByDepartment(): array
    {
        return CompetencyCategory::query()
            ->orderBy('department')
            ->orderBy('order')
            ->get(['id', 'department', 'name'])
            ->groupBy('department')
            ->map(fn ($categories) => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values())
            ->all();
    }
}
