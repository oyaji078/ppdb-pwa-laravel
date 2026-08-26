<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParentGuardianRequest extends FormRequest
{
    /**
     * Nobody is 18 at registration and nobody is 100, so the date picker is
     * bounded to a range a parent can plausibly fall in.
     */
    private const OLDEST_YEARS = 100;

    private const YOUNGEST_YEARS = 18;

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

        $shared = fn (): array => [
            'nik' => ['nullable', 'string', 'digits:16'],
            'birth_date' => ['nullable', 'date', 'after:'.self::oldestDate(), 'before:'.self::youngestDate()],
            'education' => ['nullable', Rule::in($educations)],
            'occupation' => ['nullable', 'string', 'max:100'],
            'monthly_income' => ['nullable', Rule::in($incomes)],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/', 'min:8'],
        ];

        $rules = [];

        foreach (['father', 'mother', 'guardian'] as $prefix) {
            foreach ($shared() as $field => $rule) {
                $rules[$prefix.'.'.$field] = $rule;
            }
        }

        $rules['father.name'] = ['required', 'string', 'max:150'];
        $rules['father.is_alive'] = ['nullable', 'boolean'];

        $rules['mother.name'] = ['required', 'string', 'max:150'];
        $rules['mother.is_alive'] = ['nullable', 'boolean'];

        $rules['guardian.name'] = ['nullable', 'string', 'max:150'];
        $rules['guardian.address'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $father = $this->input('father', []);
                $mother = $this->input('mother', []);
                $guardian = $this->input('guardian', []);

                // At least one contactable adult; the committee relies on it for
                // revision requests. A parent marked as no longer living is not
                // expected to supply one, so the guardian can stand in.
                $reachable = collect([$father, $mother, $guardian])
                    ->contains(fn (array $person) => filled($person['phone'] ?? null));

                if (! $reachable) {
                    $validator->errors()->add(
                        'father.phone',
                        'Isi nomor HP salah satu orang tua atau wali agar panitia dapat menghubungi Anda.'
                    );
                }

                // A living parent with no way to be reached is almost always an
                // oversight rather than a deliberate choice.
                foreach (['father' => 'Ayah', 'mother' => 'Ibu'] as $prefix => $label) {
                    $person = $prefix === 'father' ? $father : $mother;

                    if (! (bool) ($person['is_alive'] ?? true)) {
                        continue;
                    }

                    if (blank($person['phone'] ?? null) && ! $reachable) {
                        $validator->errors()->add($prefix.'.phone', sprintf('Nomor HP %s belum diisi.', $label));
                    }
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
            'father.birth_date' => 'Tanggal Lahir Ayah',
            'mother.name' => 'Nama Ibu',
            'mother.nik' => 'NIK Ibu',
            'mother.phone' => 'Nomor HP Ibu',
            'mother.birth_date' => 'Tanggal Lahir Ibu',
            'guardian.name' => 'Nama Wali',
            'guardian.nik' => 'NIK Wali',
            'guardian.phone' => 'Nomor HP Wali',
            'guardian.birth_date' => 'Tanggal Lahir Wali',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.nik.digits' => ':attribute harus 16 angka.',
            '*.phone.regex' => ':attribute hanya boleh berisi angka, spasi, dan tanda + - ( ).',
            '*.phone.min' => ':attribute terlalu pendek.',
            '*.birth_date.after' => ':attribute tidak wajar. Periksa kembali isian Anda.',
            '*.birth_date.before' => ':attribute harus berusia minimal '.self::YOUNGEST_YEARS.' tahun.',
        ];
    }

    public static function oldestDate(): string
    {
        return now()->subYears(self::OLDEST_YEARS)->toDateString();
    }

    public static function youngestDate(): string
    {
        return now()->subYears(self::YOUNGEST_YEARS)->toDateString();
    }
}
