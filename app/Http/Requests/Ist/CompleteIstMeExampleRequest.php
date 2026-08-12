<?php

namespace App\Http\Requests\Ist;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CompleteIstMeExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selected_option_key' => ['required', 'string', 'in:A,B,C,D,E'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['selected_option_key', '_token']) !== []) {
                $validator->errors()->add('request', 'Payload contains unsupported fields.');
            }
        });
    }
}
