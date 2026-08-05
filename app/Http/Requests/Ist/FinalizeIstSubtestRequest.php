<?php

namespace App\Http\Requests\Ist;

use App\Enums\Ist\IstFinalizationReason;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class FinalizeIstSubtestRequest extends FormRequest
{
    private const ANSWER_FIELDS = [
        'ist_test_question_id',
        'selected_option_key',
        'numeric_answer',
        'client_revision',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(IstFinalizationReason::class)],
            'final_answers' => ['sometimes', 'array', 'max:20'],
            'final_answers.*' => ['array:'.implode(',', self::ANSWER_FIELDS)],
            'final_answers.*.ist_test_question_id' => ['required', 'integer', 'min:1'],
            'final_answers.*.client_revision' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'final_answers.*.selected_option_key' => ['sometimes', 'nullable', 'string', 'max:10'],
            'final_answers.*.numeric_answer' => [
                'sometimes',
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== null && ! $this->isValidDecimal($value)) {
                        $fail("The {$attribute} field must be a compatible plain decimal.");
                    }
                },
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['reason', 'final_answers', '_token']) !== []) {
                $validator->errors()->add('request', 'Payload contains unsupported fields.');
            }

            $answers = $this->input('final_answers', []);

            if ($this->input('reason') === IstFinalizationReason::TIMEOUT->value
                && $answers !== []) {
                $validator->errors()->add('final_answers', 'Timeout cannot contain final answers.');
            }

            $seen = [];

            foreach ($answers as $index => $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $hasChoice = array_key_exists('selected_option_key', $answer);
                $hasNumeric = array_key_exists('numeric_answer', $answer);

                if ($hasChoice === $hasNumeric) {
                    $validator->errors()->add(
                        "final_answers.{$index}",
                        'Exactly one answer field must be present.',
                    );
                }

                $questionId = $answer['ist_test_question_id'] ?? null;

                if (is_int($questionId) && isset($seen[$questionId])) {
                    $validator->errors()->add(
                        "final_answers.{$index}.ist_test_question_id",
                        'Question IDs must be unique within final answers.',
                    );
                }

                if (is_int($questionId)) {
                    $seen[$questionId] = true;
                }
            }
        });
    }

    public function reason(): IstFinalizationReason
    {
        return IstFinalizationReason::from($this->validated('reason'));
    }

    public function finalAnswers(): array
    {
        return $this->validated('final_answers', []);
    }

    private function isValidDecimal(mixed $value): bool
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            return false;
        }

        $number = trim((string) $value);

        if (! preg_match('/^[+-]?(?:\d+(?:\.\d{0,6})?|\.\d{1,6})$/', $number)) {
            return false;
        }

        $unsigned = ltrim($number, '+-');
        [$integer] = array_pad(explode('.', $unsigned, 2), 1, '');
        $integer = ltrim($integer, '0');

        return strlen($integer === '' ? '0' : $integer) <= 14;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
