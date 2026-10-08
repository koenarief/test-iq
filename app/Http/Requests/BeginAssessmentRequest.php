<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeginAssessmentRequest extends FormRequest
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
            'instansi_id' => ['required', 'integer', 'exists:instansi,id'],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')->where('is_active', true)],
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
            'instansi_id.required' => 'Instansi wajib dipilih.',
            'instansi_id.exists' => 'Instansi tidak valid.',
            'position_id.required' => 'Jabatan/divisi yang dilamar wajib dipilih.',
            'position_id.exists' => 'Jabatan/divisi tidak valid.',
        ];
    }
}
