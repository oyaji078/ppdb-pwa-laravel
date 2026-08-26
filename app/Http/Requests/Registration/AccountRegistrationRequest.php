<?php

namespace App\Http\Requests\Registration;

use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\RegistrationWave;
use App\Services\RegistrationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Opens the applicant account that the registration form is filled in behind.
 *
 * Identity is collected here rather than in the form so a duplicate NISN is
 * caught before a registration number is spent, and so the account has
 * something to address notifications to.
 */
class AccountRegistrationRequest extends FormRequest
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
            'registration_wave_id' => ['required', 'integer', 'exists:registration_waves,id'],
            'admission_track_id' => ['required', 'integer', 'exists:admission_tracks,id'],

            'full_name' => ['required', 'string', 'max:150'],
            'nisn' => ['required', 'string', 'digits:10'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],

            // The e-mail is the login identifier for everyone in the system, so
            // it has to be unique across staff and applicant accounts alike.
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')],

            'password' => ['required', 'string', 'min:8', 'max:64', 'confirmed'],
        ];
    }

    /**
     * The wave and track must both belong to the active academic year, be
     * active, and the wave must currently be open. The NISN must not already
     * hold an account for this year.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $year = AcademicYear::current();

                if ($year === null || ! $year->registration_open) {
                    $validator->errors()->add('registration_wave_id', 'Pendaftaran sedang tidak dibuka.');

                    return;
                }

                $wave = RegistrationWave::query()
                    ->where('academic_year_id', $year->id)
                    ->find($this->integer('registration_wave_id'));

                if ($wave === null || ! $wave->isOpen()) {
                    $validator->errors()->add('registration_wave_id', 'Gelombang yang dipilih sedang tidak dibuka.');
                } elseif ($wave->isQuotaFull()) {
                    $validator->errors()->add('registration_wave_id', 'Kuota gelombang ini sudah penuh.');
                }

                $track = AdmissionTrack::query()
                    ->where('academic_year_id', $year->id)
                    ->where('is_active', true)
                    ->find($this->integer('admission_track_id'));

                if ($track === null) {
                    $validator->errors()->add('admission_track_id', 'Jalur pendaftaran yang dipilih tidak tersedia.');
                }

                $nisn = trim((string) $this->input('nisn'));

                if ($nisn === '' || $validator->errors()->has('nisn')) {
                    return;
                }

                $existing = app(RegistrationService::class)->existingRegistrationForNisn($nisn, $year->id);

                if ($existing === null) {
                    return;
                }

                // Point a returning applicant at the login form instead of a
                // dead end: forgetting an account is far more common than
                // genuinely needing a second one.
                $validator->errors()->add('nisn', $existing->isDraft()
                    ? 'NISN ini sudah memiliki akun pendaftaran. Silakan masuk memakai nomor pendaftaran dan kode akses Anda.'
                    : 'NISN ini sudah terdaftar pada tahun ajaran ini.');
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'registration_wave_id' => 'Gelombang',
            'admission_track_id' => 'Jalur Pendaftaran',
            'full_name' => 'Nama Lengkap',
            'nisn' => 'NISN',
            'phone' => 'Nomor HP',
            'email' => 'Email',
            'password' => 'Kata Sandi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk memakai email dan kata sandi Anda.',
        ];
    }
}
