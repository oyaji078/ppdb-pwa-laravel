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
     * Only three things are actually required: the name of each parent who is
     * still living, and one phone number among the three blocks. Everything
     * else may be left empty.
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

        // A parent's name is asked for only while they are recorded as living.
        // Clearing "masih hidup" is how someone says there is nothing to fill
        // in, so it has to release the rest of the block rather than leave the
        // form unsubmittable.
        $rules['father.name'] = [$this->parentIsAlive('father') ? 'required' : 'nullable', 'string', 'max:150'];
        $rules['father.is_alive'] = ['nullable', 'boolean'];

        $rules['mother.name'] = [$this->parentIsAlive('mother') ? 'required' : 'nullable', 'string', 'max:150'];
        $rules['mother.is_alive'] = ['nullable', 'boolean'];

        // The guardian block is optional as a whole, but a nameless guardian is
        // discarded on save, so details typed without a name would vanish
        // without anyone being told.
        $rules['guardian.name'] = [$this->guardianHasDetails() ? 'required' : 'nullable', 'string', 'max:150'];
        $rules['guardian.address'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    /**
     * Whether this parent is recorded as still living.
     *
     * The checkbox posts "0" through its hidden companion when cleared, so a
     * missing key means the field was never on the form at all rather than that
     * someone unticked it.
     */
    private function parentIsAlive(string $prefix): bool
    {
        return (bool) $this->input($prefix.'.is_alive', true);
    }

    /**
     * Whether anything was typed into the guardian block apart from the name.
     */
    private function guardianHasDetails(): bool
    {
        return collect(['nik', 'birth_date', 'education', 'occupation', 'monthly_income', 'phone', 'address'])
            ->contains(fn (string $field) => filled($this->input('guardian.'.$field)));
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // One contactable adult is the only hard requirement across the
                // three blocks; the committee needs somewhere to send a revision
                // request. Which of them supplies it is up to the family.
                $reachable = collect(['father', 'mother', 'guardian'])
                    ->contains(fn (string $prefix) => filled($this->input($prefix.'.phone')));

                if ($reachable) {
                    return;
                }

                // Attach the message to someone who could actually answer: a
                // parent recorded as no longer living cannot supply a number, so
                // once both are gone it is the guardian being asked.
                $target = collect(['father', 'mother'])
                    ->first(fn (string $prefix) => $this->parentIsAlive($prefix)) ?? 'guardian';

                $validator->errors()->add(
                    $target.'.phone',
                    'Isi nomor HP salah satu orang tua atau wali agar panitia dapat menghubungi Anda.'
                );
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

            // Each of these says how to satisfy the rule, not just that it was
            // broken: the way out of the first two is a checkbox further down
            // the block, which is not obvious from the field itself.
            'father.name.required' => 'Nama Ayah wajib diisi. Bila ayah sudah meninggal, hapus centang "Masih hidup" pada bagian Data Ayah.',
            'mother.name.required' => 'Nama Ibu wajib diisi. Bila ibu sudah meninggal, hapus centang "Masih hidup" pada bagian Data Ibu.',
            'guardian.name.required' => 'Nama Wali wajib diisi bila ada data wali lain yang terisi. Kosongkan seluruh bagian Data Wali bila tidak ada wali.',
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
