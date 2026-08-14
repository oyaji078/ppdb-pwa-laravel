<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParentGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Father and mother are always collected; the guardian block is optional
     * and only validated once a name is entered.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $educations = config('ppdb.reference.educations');
        $incomes = config('ppdb.reference.income_ranges');

        return [
            'father.name' => ['required', 'string', 'max:150'],
            'father.nik' => ['nullable', 'string', 'digits:16'],
            'father.birth_year' => ['nullable', 'integer', 'min:1930', 'max:'.now()->year],
            'father.education' => ['nullable', Rule::in($educations)],
            'father.occupation' => ['nullable', 'string', 'max:100'],
            'father.monthly_income' => ['nullable', Rule::in($incomes)],
            'father.phone' => ['nullable', 'string', 'max:25'],
            'father.is_alive' => ['nullable', 'boolean'],

            'mother.name' => ['required', 'string', 'max:150'],
            'mother.nik' => ['nullable', 'string', 'digits:16'],
            'mother.birth_year' => ['nullable', 'integer', 'min:1930', 'max:'.now()->year],
            'mother.education' => ['nullable', Rule::in($educations)],
            'mother.occupation' => ['nullable', 'string', 'max:100'],
            'mother.monthly_income' => ['nullable', Rule::in($incomes)],
            'mother.phone' => ['nullable', 'string', 'max:25'],
            'mother.is_alive' => ['nullable', 'boolean'],

            'guardian.name' => ['nullable', 'string', 'max:150'],
            'guardian.nik' => ['nullable', 'string', 'digits:16'],
            'guardian.birth_year' => ['nullable', 'integer', 'min:1930', 'max:'.now()->year],
            'guardian.education' => ['nullable', Rule::in($educations)],
            'guardian.occupation' => ['nullable', 'string', 'max:100'],
            'guardian.monthly_income' => ['nullable', Rule::in($incomes)],
            'guardian.phone' => ['nullable', 'string', 'max:25'],
            'guardian.address' => ['nullable', 'string', 'max:500'],

            'contact_phone_confirmed' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $father = $this->input('father', []);
                $mother = $this->input('mother', []);

                // At least one parent must be reachable by phone; the committee
                // relies on it for revision requests.
                if (blank($father['phone'] ?? null) && blank($mother['phone'] ?? null)) {
                    $validator->errors()->add(
                        'father.phone',
                        'Isi nomor HP salah satu orang tua agar panitia dapat menghubungi Anda.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'father.name' => 'Nama Ayah',
            'father.nik' => 'NIK Ayah',
            'father.phone' => 'Nomor HP Ayah',
            'father.birth_year' => 'Tahun Lahir Ayah',
            'mother.name' => 'Nama Ibu',
            'mother.nik' => 'NIK Ibu',
            'mother.phone' => 'Nomor HP Ibu',
            'mother.birth_year' => 'Tahun Lahir Ibu',
            'guardian.name' => 'Nama Wali',
            'guardian.nik' => 'NIK Wali',
            'guardian.phone' => 'Nomor HP Wali',
        ];
    }
}
