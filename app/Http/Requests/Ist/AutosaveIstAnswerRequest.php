<?php

namespace App\Http\Requests\Ist;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

final class AutosaveIstAnswerRequest extends FormRequest
{
    private const CHANGE_FIELDS = [
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
            'changes' => ['required', 'array', 'min:1', 'max:20'],
            'changes.*' => ['array:'.implode(',', self::CHANGE_FIELDS)],
            'changes.*.ist_test_question_id' => ['required', 'integer', 'min:1'],
            'changes.*.client_revision' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'changes.*.selected_option_key' => ['sometimes', 'nullable', 'string', 'max:10'],
            'changes.*.numeric_answer' => [
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
            if (array_diff(array_keys($this->all()), ['changes', '_token']) !== []) {
                $validator->errors()->add('request', 'Payload contains unsupported fields.');
            }

            $seen = [];

            foreach ($this->input('changes', []) as $index => $change) {
                if (! is_array($change)) {
                    continue;
                }

                $hasChoice = array_key_exists('selected_option_key', $change);
                $hasNumeric = array_key_exists('numeric_answer', $change);

                if ($hasChoice === $hasNumeric) {
                    $validator->errors()->add(
                        "changes.{$index}",
                        'Exactly one answer field must be present.',
                    );
                }

                $questionId = $change['ist_test_question_id'] ?? null;

                if (is_int($questionId) && isset($seen[$questionId])) {
                    $validator->errors()->add(
                        "changes.{$index}.ist_test_question_id",
                        'Question IDs must be unique within a batch.',
                    );
                }

                if (is_int($questionId)) {
                    $seen[$questionId] = true;
                }
            }
        });
    }

    public function changes(): array
    {
        return $this->validated('changes');
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
