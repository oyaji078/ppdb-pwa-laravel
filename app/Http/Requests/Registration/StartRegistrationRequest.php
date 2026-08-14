<?php

namespace App\Http\Requests\Registration;

use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\RegistrationWave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StartRegistrationRequest extends FormRequest
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
        $year = AcademicYear::current();

        return [
            'registration_wave_id' => [
                'required',
                'integer',
                'exists:registration_waves,id',
            ],
            'admission_track_id' => [
                'required',
                'integer',
                'exists:admission_tracks,id',
            ],
        ];
    }

    /**
     * The wave and track must both belong to the active academic year, be
     * active, and the wave must currently be open.
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
            },
        ];
    }
}
