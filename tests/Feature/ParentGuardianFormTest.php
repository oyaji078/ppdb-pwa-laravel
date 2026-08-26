<?php

namespace Tests\Feature;

use App\Models\ParentGuardian;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The parent step: what is required, how it is marked, and how a date of birth
 * is entered.
 */
class ParentGuardianFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_dates_of_birth_use_a_bounded_date_picker(): void
    {
        $html = $this->openParentsStep();

        foreach (['father', 'mother', 'guardian'] as $prefix) {
            $this->assertInputHas($html, $prefix.'[birth_date]', 'type="date"');
            $this->assertInputHas($html, $prefix.'[birth_date]', 'min=');
            $this->assertInputHas($html, $prefix.'[birth_date]', 'max=');
        }
    }

    /**
     * The phone rule is "at least one of the three", which the browser cannot
     * express. It must still be marked, but marking it with `required` would
     * block a submission the server would have accepted.
     */
    public function test_required_parent_fields_are_marked_including_the_phone_rule(): void
    {
        $html = $this->openParentsStep();

        foreach (['father', 'mother'] as $prefix) {
            $this->assertLabelStarred($html, $prefix.'_name');
            $this->assertLabelStarred($html, $prefix.'_phone');

            $this->assertInputHas($html, $prefix.'[name]', 'required');
            $this->assertInputLacks($html, $prefix.'[phone]', 'required');
        }

        // The guardian block is optional in full.
        $this->assertLabelNotStarred($html, 'guardian_name');
        $this->assertLabelNotStarred($html, 'guardian_phone');
    }

    public function test_the_living_checkbox_is_offered_for_both_parents_and_ticked_by_default(): void
    {
        $html = $this->openParentsStep();

        $flat = $this->flatten($html);

        foreach (['father', 'mother'] as $prefix) {
            preg_match(
                '/<input type="checkbox"[^>]*name="'.preg_quote($prefix.'[is_alive]', '/').'"[^>]*>/',
                $flat,
                $m
            );

            $this->assertNotEmpty($m, "checkbox {$prefix}[is_alive] tidak ditemukan");
            $this->assertStringContainsString('checked', $m[0], "checkbox {$prefix}[is_alive] tidak tercentang");

            // The hidden partner is what makes an unticked box reportable.
            $this->assertStringContainsString(
                '<input type="hidden" name="'.$prefix.'[is_alive]" value="0">',
                $flat
            );
        }
    }

    public function test_unticking_the_living_checkbox_is_saved(): void
    {
        $draft = $this->signedInDraft();

        $payload = $this->parents();
        unset($payload['father']['is_alive']);

        $this->post(route('registration.parents.store'), $payload)
            ->assertRedirect(route('registration.previous-school'))
            ->assertSessionHasNoErrors();

        $father = ParentGuardian::query()
            ->where('applicant_id', $draft->applicant_id)
            ->where('relationship', 'father')
            ->firstOrFail();

        $this->assertFalse($father->is_alive);
        $this->assertTrue(
            ParentGuardian::query()
                ->where('applicant_id', $draft->applicant_id)
                ->where('relationship', 'mother')
                ->value('is_alive')
        );
    }

    public function test_a_full_date_of_birth_is_stored(): void
    {
        $draft = $this->signedInDraft();

        $this->post(route('registration.parents.store'), $this->parents())
            ->assertSessionHasNoErrors();

        $father = ParentGuardian::query()
            ->where('applicant_id', $draft->applicant_id)
            ->where('relationship', 'father')
            ->firstOrFail();

        $this->assertSame('1980-04-12', $father->birth_date->toDateString());
    }

    public function test_an_implausible_parent_age_is_refused(): void
    {
        $this->signedInDraft();

        $payload = $this->parents();
        $payload['father']['birth_date'] = now()->subYears(5)->toDateString();

        $this->from(route('registration.parents'))
            ->post(route('registration.parents.store'), $payload)
            ->assertSessionHasErrors('father.birth_date');
    }

    public function test_at_least_one_contact_number_is_required(): void
    {
        $this->signedInDraft();

        $payload = $this->parents();
        $payload['father']['phone'] = '';
        $payload['mother']['phone'] = '';

        $this->from(route('registration.parents'))
            ->post(route('registration.parents.store'), $payload)
            ->assertSessionHasErrors('father.phone');
    }

    /**
     * A guardian's number satisfies the rule: a student whose parents have died
     * must still be able to complete the form.
     */
    public function test_a_guardian_number_satisfies_the_contact_rule(): void
    {
        $this->signedInDraft();

        $payload = $this->parents();
        $payload['father']['phone'] = '';
        $payload['father']['is_alive'] = '0';
        $payload['mother']['phone'] = '';
        $payload['mother']['is_alive'] = '0';
        $payload['guardian'] = ['name' => 'Paman Uji', 'phone' => '081200009999'];

        $this->post(route('registration.parents.store'), $payload)
            ->assertRedirect(route('registration.previous-school'))
            ->assertSessionHasNoErrors();
    }

    public function test_a_malformed_phone_number_is_refused(): void
    {
        $this->signedInDraft();

        $payload = $this->parents();
        $payload['father']['phone'] = 'nomor-saya';

        $this->from(route('registration.parents'))
            ->post(route('registration.parents.store'), $payload)
            ->assertSessionHasErrors('father.phone');
    }

    public function test_a_nik_that_is_not_sixteen_digits_is_refused(): void
    {
        $this->signedInDraft();

        $payload = $this->parents();
        $payload['father']['nik'] = '12345';

        $this->from(route('registration.parents'))
            ->post(route('registration.parents.store'), $payload)
            ->assertSessionHasErrors('father.nik');
    }

    // -- helpers --------------------------------------------------------------

    private function signedInDraft(): Registration
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createAccountFor($config);

        $this->actingAsApplicant($draft);

        return $draft;
    }

    private function openParentsStep(): string
    {
        $draft = $this->signedInDraft();

        return $this->actingAsApplicant($draft)
            ->get(route('registration.parents'))
            ->assertOk()
            ->getContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function parents(): array
    {
        return [
            'father' => [
                'name' => 'Muhammad Yusuf',
                'nik' => '5203010101800001',
                'birth_date' => '1980-04-12',
                'education' => 'SMA/Sederajat',
                'occupation' => 'Petani',
                'monthly_income' => 'Rp1.000.000 - Rp2.000.000',
                'phone' => '081234567891',
                'is_alive' => '1',
            ],
            'mother' => [
                'name' => 'Siti Aminah',
                'nik' => '5203010101850001',
                'birth_date' => '1985-09-30',
                'education' => 'SMP/Sederajat',
                'occupation' => 'Ibu Rumah Tangga',
                'monthly_income' => 'Tidak Berpenghasilan',
                'phone' => '081234567892',
                'is_alive' => '1',
            ],
            'guardian' => ['name' => ''],
        ];
    }

    private function flatten(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }

    private function tagFor(string $html, string $name): string
    {
        preg_match('/<input[^>]*name="'.preg_quote($name, '/').'"[^>]*>/', $this->flatten($html), $m);

        $this->assertNotEmpty($m, "input {$name} tidak ditemukan");

        return $m[0];
    }

    private function assertInputHas(string $html, string $name, string $needle): void
    {
        $this->assertStringContainsString($needle, $this->tagFor($html, $name), "input {$name} tidak memuat {$needle}");
    }

    private function assertInputLacks(string $html, string $name, string $needle): void
    {
        $this->assertStringNotContainsString($needle, $this->tagFor($html, $name), "input {$name} seharusnya tanpa {$needle}");
    }

    private function labelFor(string $html, string $for): string
    {
        preg_match('/<label[^>]*for="'.preg_quote($for, '/').'"[^>]*>.*?<\/label>/', $this->flatten($html), $m);

        $this->assertNotEmpty($m, "label {$for} tidak ditemukan");

        return $m[0];
    }

    private function assertLabelStarred(string $html, string $for): void
    {
        $this->assertStringContainsString('>*<', $this->labelFor($html, $for), "label {$for} belum diberi bintang");
    }

    private function assertLabelNotStarred(string $html, string $for): void
    {
        $this->assertStringNotContainsString('>*<', $this->labelFor($html, $for), "label {$for} tidak seharusnya berbintang");
    }
}
