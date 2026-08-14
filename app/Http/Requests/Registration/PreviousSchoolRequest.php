<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviousSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:150'],
            'npsn' => ['nullable', 'string', 'digits:8'],
            'nsm' => ['nullable', 'string', 'max:20'],
            'school_type' => ['required', Rule::in(array_keys(config('ppdb.reference.school_types')))],
            'school_status' => ['required', Rule::in(array_keys(config('ppdb.reference.school_statuses')))],
            'province' => ['nullable', 'string', 'max:100'],
            'regency' => ['nullable', 'string', 'max:100'],
            'graduation_year' => ['required', 'integer', 'min:'.(now()->year - 15), 'max:'.(now()->year + 1)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'npsn.digits' => 'NPSN harus terdiri dari 8 angka.',
            'graduation_year.min' => 'Tahun lulus tidak wajar. Periksa kembali isian Anda.',
            'graduation_year.max' => 'Tahun lulus tidak boleh melebihi tahun depan.',
        ];
    }
}
