<?php

namespace App\Http\Requests\Registration;

use App\Services\RegistrationService;
use App\Support\RegistrationDraft;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BiodataRequest extends FormRequest
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
            'nisn' => ['required', 'string', 'digits:10'],
            'nik' => ['required', 'string', 'digits:16'],
            'family_card_number' => ['nullable', 'string', 'digits:16'],
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['required', Rule::in(array_keys(config('ppdb.reference.genders')))],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today', 'after:'.now()->subYears(40)->toDateString()],
            'religion' => ['required', Rule::in(config('ppdb.reference.religions'))],
            'child_order' => ['nullable', 'integer', 'min:1', 'max:20'],
            'siblings_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
        ];
    }

    /**
     * Catch a NISN that already belongs to a submitted registration in the
     * same academic year, so the applicant finds out here rather than at the
     * final submit.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $draft = app(RegistrationDraft::class)->current();
                $nisn = (string) $this->input('nisn');

                if ($draft === null || $nisn === '') {
                    return;
                }

                if (app(RegistrationService::class)->nisnTaken($nisn, $draft->academic_year_id, $draft->id)) {
                    $validator->errors()->add('nisn', 'NISN ini sudah terdaftar pada tahun ajaran yang sama.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nisn.digits' => 'NISN harus terdiri dari 10 angka.',
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'family_card_number.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 angka.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka dan tanda +, -, spasi, atau kurung.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'birth_date.after' => 'Tanggal lahir tidak wajar. Periksa kembali isian Anda.',
        ];
    }
}
