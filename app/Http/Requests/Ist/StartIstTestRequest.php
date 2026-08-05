<?php

namespace App\Http\Requests\Ist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StartIstTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('participant_name'))) {
            $this->merge(['participant_name' => trim($this->input('participant_name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'participant_name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:10', 'max:100'],
            'gender' => ['required', 'string', 'size:1', Rule::in(['L', 'P'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $foreign = array_diff(array_keys($this->all()), [
                'participant_name', 'age', 'gender', '_token',
            ]);

            if ($foreign !== []) {
                $validator->errors()->add('request', 'Payload contains unsupported fields.');
            }
        });
    }

    public function participantData(): array
    {
        return $this->safe()->only(['participant_name', 'age', 'gender']);
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
