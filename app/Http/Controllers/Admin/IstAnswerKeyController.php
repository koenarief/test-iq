<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IstAnswerKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IstAnswerKeyController extends Controller
{
    private const SUBTESTS = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];

    public function index(Request $request): Response
    {
        $subtest = $request->string('subtest')->value() ?: null;

        $answerKeys = IstAnswerKey::query()
            ->when($subtest, fn ($query) => $query->where('subtest', $subtest))
            ->orderBy('subtest')
            ->orderBy('question_number')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/IstAnswerKeys/Index', [
            'answerKeys' => $answerKeys,
            'subtests' => self::SUBTESTS,
            'filters' => ['subtest' => $subtest],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/IstAnswerKeys/Create', [
            'subtests' => self::SUBTESTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        IstAnswerKey::create($data);

        return redirect()
            ->route('admin.ist-answer-keys.index', ['subtest' => $data['subtest']])
            ->with('success', 'Kunci jawaban berhasil ditambahkan.');
    }

    public function edit(IstAnswerKey $answerKey): Response
    {
        return Inertia::render('Admin/IstAnswerKeys/Edit', [
            'answerKey' => $answerKey,
            'subtests' => self::SUBTESTS,
        ]);
    }

    public function update(Request $request, IstAnswerKey $answerKey): RedirectResponse
    {
        $data = $this->validated($request, $answerKey);

        $answerKey->update($data);

        return redirect()
            ->route('admin.ist-answer-keys.index', ['subtest' => $data['subtest']])
            ->with('success', 'Kunci jawaban berhasil diperbarui.');
    }

    public function destroy(IstAnswerKey $answerKey): RedirectResponse
    {
        $subtest = $answerKey->subtest;

        $answerKey->delete();

        return redirect()
            ->route('admin.ist-answer-keys.index', ['subtest' => $subtest])
            ->with('success', 'Kunci jawaban berhasil dihapus.');
    }

    private function validated(Request $request, ?IstAnswerKey $answerKey = null): array
    {
        $validator = Validator::make($request->all(), [
            'subtest' => ['required', Rule::in(self::SUBTESTS)],
            'question_number' => [
                'required', 'integer', 'min:1',
                Rule::unique('ist_answer_keys', 'question_number')
                    ->where(fn ($query) => $query->where('subtest', $request->input('subtest')))
                    ->ignore($answerKey?->id),
            ],
            'correct_answer' => ['required', 'string'],
            'score_weight' => ['required', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->input('subtest') !== 'GE') {
                return;
            }

            $decoded = json_decode((string) $request->input('correct_answer'), true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                $validator->errors()->add(
                    'correct_answer',
                    'Untuk subtes GE, jawaban harus berupa JSON kata kunci yang valid, contoh: {"score_2":["kata"],"score_1":["kata lain"]}.',
                );
            }
        });

        return $validator->validate();
    }
}
