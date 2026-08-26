<?php

namespace App\Http\Requests\Registration;

use App\Models\Program;
use App\Support\ApplicantSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProgramSelectionRequest extends FormRequest
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
            'program_id' => ['required', 'integer', 'exists:programs,id'],
        ];
    }

    /**
     * The chosen program must belong to the same academic year and be active.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $draft = app(ApplicantSession::class)->registration();

                if ($draft === null) {
                    return;
                }

                $program = Program::query()
                    ->where('academic_year_id', $draft->academic_year_id)
                    ->where('is_active', true)
                    ->find($this->integer('program_id'));

                if ($program === null) {
                    $validator->errors()->add('program_id', 'Program yang dipilih tidak tersedia untuk tahun ajaran ini.');
                }
            },
        ];
    }
}
