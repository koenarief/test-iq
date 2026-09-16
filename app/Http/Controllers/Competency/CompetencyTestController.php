<?php

namespace App\Http\Controllers\Competency;

use App\Actions\Competency\SelectCompetencyQuestionsForTest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartCompetencyTestRequest;
use App\Models\CompetencyAnswer;
use App\Models\CompetencyQuestionOption;
use App\Models\CompetencyTest;
use App\Models\Merchant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyTestController extends Controller
{
    private const SESSION_MERCHANT_KEY = 'competency.merchant_id';

    public function index(Request $request, ?Merchant $merchant = null): Response
    {
        if ($merchant !== null && ! $merchant->is_active) {
            abort(404);
        }

        $request->session()->put(self::SESSION_MERCHANT_KEY, $merchant?->id);

        return Inertia::render('Competency/Biodata', [
            'merchantName' => $merchant?->name,
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function start(
        StartCompetencyTestRequest $request,
        SelectCompetencyQuestionsForTest $selector,
    ) {
        $merchantId = $request->session()->get(self::SESSION_MERCHANT_KEY);

        if ($merchantId !== null && ! Merchant::where('id', $merchantId)->where('is_active', true)->exists()) {
            $merchantId = null;
        }

        $request->session()->forget(self::SESSION_MERCHANT_KEY);

        $competencyTest = CompetencyTest::create([
            'participant_name' => $request->participant_name,
            'age' => $request->age,
            'gender' => $request->gender,
            'department' => $request->department,
            'status' => 'draft',
            'merchant_id' => $merchantId,
        ]);

        $selector->handle($competencyTest);

        return redirect()->route('competency.instruction', $competencyTest->id);
    }

    public function instruction(CompetencyTest $competencyTest): Response
    {
        $categories = $competencyTest->testQuestions()
            ->join('competency_categories', 'competency_categories.id', '=', 'competency_test_questions.competency_category_id')
            ->selectRaw('competency_categories.id as id, competency_categories.name as name, COUNT(*) as question_count')
            ->groupBy('competency_categories.id', 'competency_categories.name', 'competency_categories.order')
            ->orderBy('competency_categories.order')
            ->get();

        return Inertia::render('Competency/Instruction', [
            'competencyTest' => [
                ...$competencyTest->toArray(),
                'department_label' => $this->departmentLabel($competencyTest->department),
            ],
            'categories' => $categories,
            'totalQuestions' => $categories->sum('question_count'),
            'durationMinutes' => (int) config('competency.duration_minutes'),
        ]);
    }

    public function test(CompetencyTest $competencyTest)
    {
        if ($competencyTest->status === 'completed') {
            return Inertia::location(route('competency.result', $competencyTest));
        }

        if (! $competencyTest->started_at) {
            $competencyTest->update([
                'started_at' => now(),
                'status' => 'in_progress',
            ]);

            $competencyTest->refresh();
        }

        $questions = $competencyTest->testQuestions()
            ->with([
                'category:id,name',
                'question:id,question_text',
                'question.options:id,competency_question_id,label,option_text',
            ])
            ->orderBy('order')
            ->get()
            ->map(fn ($testQuestion) => [
                'id' => $testQuestion->competency_question_id,
                'order' => $testQuestion->order,
                'category_name' => $testQuestion->category->name,
                'question_text' => $testQuestion->question->question_text,
                'options' => $testQuestion->question->options
                    ->sortBy('label')
                    ->values()
                    ->map(fn ($option) => [
                        'label' => $option->label,
                        'text' => $option->option_text,
                    ]),
            ])
            ->values();

        return Inertia::render('Competency/Test', [
            'competencyTest' => $competencyTest,
            'questions' => $questions,
            'durationMinutes' => (int) config('competency.duration_minutes'),
        ]);
    }

    public function submit(Request $request, CompetencyTest $competencyTest)
    {
        if ($competencyTest->status === 'completed') {
            return redirect()->route('competency.result', $competencyTest);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:submitted,timeout'],
            'answers' => ['present', 'array'],
            'answers.*' => ['nullable', 'string', 'in:A,B,C,D,E'],
        ]);

        $reason = $validated['reason'];
        $answersPayload = $validated['answers'];

        $testQuestions = $competencyTest->testQuestions()->get();

        if ($reason === 'submitted') {
            $answeredCount = 0;

            foreach ($testQuestions as $testQuestion) {
                if (! empty($answersPayload[$testQuestion->competency_question_id])) {
                    $answeredCount++;
                }
            }

            if ($answeredCount !== $testQuestions->count()) {
                return back()->withErrors([
                    'answers' => 'Seluruh soal harus dijawab sebelum tes disubmit.',
                ]);
            }
        }

        if ($reason === 'timeout') {
            if (! $competencyTest->started_at) {
                abort(409, 'Waktu pengerjaan tes belum dimulai.');
            }

            $durationMinutes = (int) config('competency.duration_minutes');
            $deadline = Carbon::parse($competencyTest->started_at)->copy()->addMinutes($durationMinutes);

            if (now()->addSeconds(15)->lt($deadline)) {
                abort(409, 'Batas waktu tes belum habis.');
            }
        }

        DB::transaction(function () use ($answersPayload, $competencyTest, $testQuestions) {
            $lockedTest = CompetencyTest::query()
                ->whereKey($competencyTest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTest->status === 'completed') {
                return;
            }

            $lockedTest->answers()->delete();

            $optionsByQuestion = CompetencyQuestionOption::query()
                ->whereIn('competency_question_id', $testQuestions->pluck('competency_question_id'))
                ->get()
                ->groupBy('competency_question_id');

            foreach ($testQuestions as $testQuestion) {
                $label = $answersPayload[$testQuestion->competency_question_id] ?? null;

                if ($label === null) {
                    continue;
                }

                $option = $optionsByQuestion
                    ->get($testQuestion->competency_question_id, collect())
                    ->firstWhere('label', $label);

                if (! $option) {
                    continue;
                }

                CompetencyAnswer::create([
                    'competency_test_id' => $lockedTest->id,
                    'competency_question_id' => $testQuestion->competency_question_id,
                    'competency_category_id' => $testQuestion->competency_category_id,
                    'selected_label' => $label,
                    'points' => $option->points,
                ]);
            }

            $answers = $lockedTest->answers()->get();

            $categoryIds = $testQuestions->pluck('competency_category_id')->unique();

            $categoryAverages = $categoryIds->map(function ($categoryId) use ($answers) {
                $categoryAnswers = $answers->where('competency_category_id', $categoryId);

                if ($categoryAnswers->isEmpty()) {
                    return 0;
                }

                return $categoryAnswers->avg('points');
            });

            $totalScore = $categoryAverages->isEmpty()
                ? 0
                : round($categoryAverages->avg(), 2);

            $lockedTest->update([
                'finished_at' => now(),
                'status' => 'completed',
                'total_score' => $totalScore,
            ]);
        });

        return redirect()->route('competency.result', $competencyTest);
    }

    public function result(CompetencyTest $competencyTest): Response
    {
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

        return Inertia::render('Competency/Result', [
            'competencyTest' => [
                ...$competencyTest->toArray(),
                'department_label' => $this->departmentLabel($competencyTest->department),
            ],
            'breakdown' => $breakdown,
            'overallPercentage' => round(((float) $competencyTest->total_score / 5) * 100, 1),
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

    private function departmentLabel(string $department): string
    {
        return config("competency.departments.{$department}.label", $department);
    }
}
