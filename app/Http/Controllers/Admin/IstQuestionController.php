<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstQuestionOption;
use App\Models\Ist\IstSubtest;
use App\Support\Ist\IstAnswerType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IstQuestionController extends Controller
{
    private const OPTION_KEYS = ['A', 'B', 'C', 'D', 'E'];

    private const DIFFICULTIES = ['easy', 'medium', 'hard'];

    private const KINDS = [IstQuestion::KIND_SCORED, IstQuestion::KIND_EXAMPLE];

    public function index(Request $request): Response
    {
        $subtests = IstSubtest::query()->orderBy('sequence')->get(['id', 'code', 'name', 'sequence']);

        $selectedSubtestId = $request->integer('subtest_id') ?: $subtests->first()?->id;

        $questions = IstQuestion::query()
            ->with('subtest:id,code,name')
            ->withCount('options')
            ->when($selectedSubtestId, fn ($query) => $query->where('ist_subtest_id', $selectedSubtestId))
            ->when($request->filled('kind'), fn ($query) => $query->where('kind', $request->string('kind')))
            ->when($request->filled('search'), fn ($query) => $query->where('prompt', 'like', '%'.$request->string('search').'%'))
            ->orderBy('kind')
            ->orderBy('question_number')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/IstQuestions/Index', [
            'subtests' => $subtests,
            'questions' => $questions,
            'filters' => $request->only(['subtest_id', 'kind', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/IstQuestions/Create', $this->formProps());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data) {
                $version = IstQuestion::where('ist_subtest_id', $data['question']['ist_subtest_id'])
                    ->where('is_active', true)
                    ->max('version') ?? 1;

                $question = IstQuestion::create([
                    ...$data['question'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'version' => $version,
                ]);

                $this->syncOptions($question, $data['options']);
            });
        } catch (QueryException $exception) {
            throw $this->duplicateQuestionException($exception);
        }

        return redirect()
            ->route('admin.ist-questions.index', ['subtest_id' => $data['question']['ist_subtest_id']])
            ->with('success', 'Soal berhasil ditambahkan.');
    }

    public function edit(IstQuestion $question): Response
    {
        $question->load(['options' => fn ($query) => $query->orderBy('display_order')]);

        return Inertia::render('Admin/IstQuestions/Edit', [
            ...$this->formProps(),
            'question' => $question,
        ]);
    }

    public function update(Request $request, IstQuestion $question): RedirectResponse
    {
        $data = $this->validated($request, $question);

        try {
            DB::transaction(function () use ($question, $data) {
                $question->update([
                    ...$data['question'],
                    'updated_by' => auth()->id(),
                ]);

                $this->syncOptions($question, $data['options']);
            });
        } catch (QueryException $exception) {
            throw $this->duplicateQuestionException($exception);
        }

        return redirect()
            ->route('admin.ist-questions.index', ['subtest_id' => $data['question']['ist_subtest_id']])
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(IstQuestion $question): RedirectResponse
    {
        $subtestId = $question->ist_subtest_id;

        DB::transaction(function () use ($question) {
            $question->options()->delete();
            $question->delete();
        });

        return redirect()
            ->route('admin.ist-questions.index', ['subtest_id' => $subtestId])
            ->with('success', 'Soal berhasil dihapus.');
    }

    private function formProps(): array
    {
        return [
            'subtests' => IstSubtest::query()
                ->orderBy('sequence')
                ->get(['id', 'code', 'name', 'question_count', 'default_answer_type']),
            'answerTypes' => IstAnswerType::all(),
            'difficulties' => self::DIFFICULTIES,
            'kinds' => self::KINDS,
            'optionKeys' => self::OPTION_KEYS,
        ];
    }

    /**
     * Mirrors the invariants IstQuestionSnapshotService enforces when it turns
     * a master question into a participant-facing snapshot, so an admin edit
     * can never save a combination that would later fail a real test run.
     */
    private function validated(Request $request, ?IstQuestion $question = null): array
    {
        $answerType = $request->input('answer_type');
        $isNumeric = $answerType === IstAnswerType::NUMERIC;

        $validator = Validator::make($request->all(), [
            'ist_subtest_id' => ['required', 'integer', Rule::exists('ist_subtests', 'id')],
            'kind' => ['required', Rule::in(self::KINDS)],
            'question_number' => [
                'required', 'integer', 'min:1',
                Rule::unique('ist_questions', 'question_number')
                    ->where(fn ($query) => $query
                        ->where('ist_subtest_id', $request->input('ist_subtest_id'))
                        ->where('kind', $request->input('kind'))
                        ->whereNull('deleted_at'))
                    ->ignore($question?->id),
            ],
            'display_order' => ['required', 'integer', 'min:1'],
            'answer_type' => ['required', Rule::in(IstAnswerType::all())],
            'prompt' => ['nullable', 'string'],
            'image_disk' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'example_explanation' => ['nullable', 'string'],
            'numeric_answer_key' => [
                $isNumeric ? 'required' : 'nullable',
                'nullable', 'regex:/^[+-]?(?:\d+(?:\.\d{1,6})?|\.\d{1,6})$/',
            ],
            'max_score' => ['required', 'numeric', 'min:0'],
            'difficulty' => ['required', Rule::in(self::DIFFICULTIES)],
            'is_active' => ['boolean'],
            'options' => $isNumeric ? ['array'] : ['required', 'array'],
            'options.*.option_key' => ['required_with:options', Rule::in(self::OPTION_KEYS)],
            'options.*.option_text' => ['nullable', 'string'],
            'options.*.image_disk' => ['nullable', 'string', 'max:255'],
            'options.*.image_path' => ['nullable', 'string', 'max:2048'],
            'options.*.image_alt' => ['nullable', 'string', 'max:255'],
            'options.*.is_correct' => ['boolean'],
            'options.*.score_value' => ['required_with:options', 'integer'],
        ]);

        $validator->after(function ($validator) use ($request, $answerType, $isNumeric) {
            $this->assertOptionInvariants($validator, $request, $answerType, $isNumeric);
        });

        $validated = $validator->validate();

        return [
            'question' => [
                'ist_subtest_id' => $validated['ist_subtest_id'],
                'kind' => $validated['kind'],
                'question_number' => $validated['question_number'],
                'display_order' => $validated['display_order'],
                'answer_type' => $validated['answer_type'],
                'prompt' => $validated['prompt'] ?? null,
                'image_disk' => $validated['image_disk'] ?? null,
                'image_path' => $validated['image_path'] ?? null,
                'image_alt' => $validated['image_alt'] ?? null,
                'example_explanation' => $validated['example_explanation'] ?? null,
                'numeric_answer_key' => $isNumeric ? $validated['numeric_answer_key'] : null,
                'max_score' => $validated['max_score'],
                'difficulty' => $validated['difficulty'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ],
            'options' => $isNumeric ? [] : $this->normalizeOptions($validated['options']),
        ];
    }

    private function assertOptionInvariants($validator, Request $request, ?string $answerType, bool $isNumeric): void
    {
        if ($isNumeric || $validator->errors()->isNotEmpty()) {
            return;
        }

        $options = collect($request->input('options', []));

        if ($options->count() !== 5 || $options->pluck('option_key')->unique()->sort()->values()->all() !== self::OPTION_KEYS) {
            $validator->errors()->add('options', 'Harus tepat 5 opsi dengan kunci A, B, C, D, E masing-masing satu kali.');

            return;
        }

        $scoreByKey = $options->pluck('score_value', 'option_key')->map(fn ($value) => (int) $value);
        $correctByKey = $options->pluck('is_correct', 'option_key')->map(fn ($value) => (bool) $value);

        if ($answerType === IstAnswerType::SINGLE_CHOICE_WEIGHTED) {
            foreach ($scoreByKey as $key => $score) {
                if ($score < 0 || $score > 3) {
                    $validator->errors()->add('options', "Skor opsi {$key} harus berupa bilangan bulat 0 sampai 3.");
                }

                if (($score === 3) !== $correctByKey[$key]) {
                    $validator->errors()->add('options', "Tanda jawaban benar pada opsi {$key} harus konsisten dengan skor 3.");
                }
            }

            if ($scoreByKey->filter(fn ($score) => $score === 3)->count() !== 1) {
                $validator->errors()->add('options', 'Harus ada tepat satu opsi dengan skor 3 sebagai jawaban benar.');
            }
        } else {
            foreach ($scoreByKey as $key => $score) {
                if (! in_array($score, [0, 1], true)) {
                    $validator->errors()->add('options', "Skor opsi {$key} harus 0 atau 1.");
                }

                if (($score === 1) !== $correctByKey[$key]) {
                    $validator->errors()->add('options', "Tanda jawaban benar pada opsi {$key} harus konsisten dengan skornya.");
                }
            }

            if ($correctByKey->filter(fn ($correct) => $correct)->count() !== 1) {
                $validator->errors()->add('options', 'Harus ada tepat satu opsi yang ditandai sebagai jawaban benar.');
            }
        }
    }

    private function normalizeOptions(array $options): array
    {
        return collect($options)
            ->sortBy('option_key')
            ->values()
            ->map(fn ($option, $index) => [
                'option_key' => $option['option_key'],
                'option_text' => $option['option_text'] ?? null,
                'image_disk' => $option['image_disk'] ?? null,
                'image_path' => $option['image_path'] ?? null,
                'image_alt' => $option['image_alt'] ?? null,
                'display_order' => $index + 1,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'score_value' => (int) ($option['score_value'] ?? 0),
                'is_active' => true,
            ])
            ->all();
    }

    private function syncOptions(IstQuestion $question, array $options): void
    {
        $desiredKeys = array_column($options, 'option_key');

        foreach ($options as $data) {
            $option = IstQuestionOption::withTrashed()
                ->where('ist_question_id', $question->id)
                ->where('option_key', $data['option_key'])
                ->first();

            if (! $option) {
                $option = new IstQuestionOption(['ist_question_id' => $question->id]);
            } elseif ($option->trashed()) {
                $option->restore();
            }

            $option->fill($data);
            $option->ist_question_id = $question->id;
            $option->save();
        }

        IstQuestionOption::query()
            ->where('ist_question_id', $question->id)
            ->when($desiredKeys !== [], fn ($query) => $query->whereNotIn('option_key', $desiredKeys))
            ->delete();
    }

    private function duplicateQuestionException(QueryException $exception): ValidationException
    {
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }

        return ValidationException::withMessages([
            'question_number' => 'Kombinasi subtes, jenis, dan nomor soal ini sudah dipakai (termasuk soal yang pernah dihapus). Gunakan nomor lain.',
        ]);
    }
}
