<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartCompetencyTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'between:10,100'],
            'gender' => ['required', 'in:L,P'],
            'department' => ['required', Rule::in(array_keys(config('competency.departments')))],
        ];
    }

    public function messages(): array
    {
        return [
            'participant_name.required' => 'Nama wajib diisi.',
            'age.required' => 'Usia wajib diisi.',
            'age.integer' => 'Usia harus berupa angka.',
            'age.between' => 'Usia tidak valid.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'department.required' => 'Divisi wajib dipilih.',
            'department.in' => 'Divisi tidak valid.',
        ];
    }
}
