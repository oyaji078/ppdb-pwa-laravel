<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusCheckRequest extends FormRequest
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
            'registration_number' => ['required', 'string', 'digits:10'],
            'access_code' => ['required', 'string', 'size:'.config('ppdb.access_code.length')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'registration_number' => preg_replace('/\D+/', '', (string) $this->input('registration_number')),
            'access_code' => strtoupper(trim((string) $this->input('access_code'))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_number.digits' => 'Nomor pendaftaran terdiri dari 10 angka.',
            'access_code.size' => 'Kode akses terdiri dari :size karakter.',
        ];
    }
}
