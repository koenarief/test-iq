<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\CandidateAssessment;
use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Position;
use App\Models\Ist\IstTest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $merchant = $request->user()->merchant;
        $merchantId = $merchant->id;

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'position_id' => ['nullable', 'integer'],
        ]);
        $filters = [
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
            'position_id' => $filters['position_id'] ?? '',
        ];

        return Inertia::render('Merchant/Dashboard', [
            'stats' => [
                'ist_total' => IstTest::query()->where('merchant_id', $merchantId)->count(),
                'ist_completed' => IstTest::query()->where('merchant_id', $merchantId)
                    ->where('status', IstTest::STATUS_COMPLETED)->count(),
                'disc_total' => DiscTest::query()->where('merchant_id', $merchantId)->count(),
                'disc_completed' => DiscTest::query()->where('merchant_id', $merchantId)
                    ->where('status', 'completed')->count(),
                'competency_total' => CompetencyTest::query()->where('merchant_id', $merchantId)->count(),
                'competency_completed' => CompetencyTest::query()->where('merchant_id', $merchantId)
                    ->where('status', 'completed')->count(),
            ],
            'ranking' => $this->ranking($merchantId, $filters),
            'filters' => $filters,
            'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
            'startUrl' => route('assessment.start.merchant', $merchant),
        ]);
    }

    /**
     * Rekap & peringkat kandidat yang mengikuti tes lewat link merchant ini.
     * Kandidat dikaitkan ke merchant lewat merchant_id pada tes DISC/IST/Kompetensi
     * mereka, jadi hanya peserta merchant terkait yang muncul.
     */
    private function ranking(int $merchantId, array $filters = []): array
    {
        $assessments = CandidateAssessment::query()
            ->with(['candidate:id,name', 'position:id,name'])
            ->where(function ($query) use ($merchantId) {
                $query->whereHas('discTest', fn ($q) => $q->where('merchant_id', $merchantId))
                    ->orWhereHas('istTest', fn ($q) => $q->where('merchant_id', $merchantId))
                    ->orWhereHas('competencyTest', fn ($q) => $q->where('merchant_id', $merchantId));
            })
            ->when($filters['position_id'] ?? null, fn ($q, $v) => $q->where('position_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->get()
            ->sortBy([
                fn ($a, $b) => ($b->final_score ?? -1) <=> ($a->final_score ?? -1),
                fn ($a, $b) => ($b->job_fit_score ?? -1) <=> ($a->job_fit_score ?? -1),
                fn ($a, $b) => ($b->assessment_score ?? -1) <=> ($a->assessment_score ?? -1),
            ])
            ->values();

        return $assessments->map(fn (CandidateAssessment $a, int $i) => [
            'rank' => $i + 1,
            'id' => $a->id,
            'name' => $a->candidate?->name,
            'position' => $a->position?->name,
            'disc_score' => $a->disc_score,
            'ist_score' => $a->ist_score,
            'competency_score' => $a->competency_score,
            'assessment_score' => $a->assessment_score,
            'gate_passed' => $a->gate_passed,
            'job_fit_score' => $a->job_fit_score,
            'final_score' => $a->final_score,
            'decision' => $a->decision,
        ])->all();
    }
}
