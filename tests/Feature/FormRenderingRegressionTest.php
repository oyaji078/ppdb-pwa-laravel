<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Guards three bug classes that the workflow tests structurally cannot catch,
 * because they post pre-built PHP arrays and never exercise the rendered HTML:
 *
 *  1. Nested field names must reach the browser as "father[name]". Posting
 *     ['father' => ['name' => ...]] in a test bypasses form encoding entirely,
 *     so only an assertion on the markup can catch a dotted name attribute.
 *  2. Controllers that read an optional validated key with "?:" crash when the
 *     form omits the field, since "?:" still evaluates a missing index.
 *  3. Route::resource generates actions the controller may not implement.
 */
class FormRenderingRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PHP rewrites dots in top-level request keys to underscores, so a rendered
     * name="father.name" arrives as "father_name" and the nested validation rule
     * never sees it — every parent field reads as empty however it was filled in.
     */
    public function test_parent_fields_render_as_array_names_not_dotted_names(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $response = $this->actingAsApplicant($this->createAccountFor($config))
            ->get(route('registration.parents'))
            ->assertOk();

        foreach (['father', 'mother', 'guardian'] as $prefix) {
            $response->assertSee('name="'.$prefix.'[name]"', false);
            $response->assertSee('name="'.$prefix.'[phone]"', false);
            $response->assertDontSee('name="'.$prefix.'.name"', false);
            $response->assertDontSee('name="'.$prefix.'.phone"', false);
        }
    }

    /**
     * Each field should offer the input a phone actually makes easy: a date
     * picker for a date, a numeric keypad for digits, a phone keypad for a phone
     * number. Typing a birth date into a free-text box on a phone is miserable.
     */
    public function test_each_field_uses_the_input_type_that_suits_it(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createAccountFor($config);

        $biodata = $this->actingAsApplicant($draft)
            ->get(route('registration.biodata'))->assertOk()->getContent();

        $this->assertInputHas($biodata, 'birth_date', 'type="date"');
        $this->assertInputHas($biodata, 'birth_date', 'max=');
        $this->assertInputHas($biodata, 'phone', 'type="tel"');
        $this->assertInputHas($biodata, 'email', 'type="email"');
        $this->assertInputHas($biodata, 'nisn', 'inputmode="numeric"');
        $this->assertInputHas($biodata, 'nik', 'inputmode="numeric"');
        $this->assertInputHas($biodata, 'child_order', 'type="number"');

        $address = $this->actingAsApplicant($draft)
            ->get(route('registration.address'))->assertOk()->getContent();

        $this->assertInputHas($address, 'postal_code', 'inputmode="numeric"');
        $this->assertInputHas($address, 'postal_code', 'autocomplete="postal-code"');

        $school = $this->actingAsApplicant($draft)
            ->get(route('registration.previous-school'))->assertOk()->getContent();

        $this->assertInputHas($school, 'graduation_year', 'type="number"');
        $this->assertInputHas($school, 'npsn', 'inputmode="numeric"');

        $parents = $this->actingAsApplicant($draft)
            ->get(route('registration.parents'))->assertOk()->getContent();

        $this->assertInputHas($parents, 'father[phone]', 'type="tel"');
        $this->assertInputHas($parents, 'father[birth_date]', 'type="date"');
    }

    /**
     * The account form is where a visitor first meets the system, so its inputs
     * matter most.
     */
    public function test_the_account_form_uses_email_and_password_inputs(): void
    {
        $this->createPpdbConfiguration();

        $html = $this->get(route('registration.start'))->assertOk()->getContent();

        $this->assertInputHas($html, 'email', 'type="email"');
        $this->assertInputHas($html, 'phone', 'type="tel"');
        $this->assertInputHas($html, 'password', 'type="password"');
        $this->assertInputHas($html, 'password', 'autocomplete="new-password"');
        $this->assertInputHas($html, 'password_confirmation', 'type="password"');
    }

    /**
     * Inputs are rendered with one attribute per line, so the tag is collapsed
     * before being searched.
     */
    private function assertInputHas(string $html, string $name, string $needle): void
    {
        $flat = preg_replace('/\s+/', ' ', $html);

        preg_match('/<input[^>]*name="'.preg_quote($name, '/').'"[^>]*>/', $flat, $matches);

        $this->assertNotEmpty($matches, "input {$name} tidak ditemukan");
        $this->assertStringContainsString($needle, $matches[0], "input {$name} tidak memuat {$needle}");
    }

    public function test_field_name_helper_converts_dots_to_brackets(): void
    {
        $this->assertSame('father[name]', field_name('father.name'));
        $this->assertSame('a[b][c]', field_name('a.b.c'));
        $this->assertSame('flat', field_name('flat'), 'names without dots must pass through untouched');
    }

    /**
     * A duplicate id attribute is invalid HTML: $attributes->get('id') leaves the
     * attribute in the bag, so merge() used to emit it a second time.
     */
    public function test_inputs_do_not_render_a_duplicate_id_attribute(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $html = $this->actingAsApplicant($this->createAccountFor($config))
            ->get(route('registration.parents'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<input\b[^>]*>/s', $html, $matches);
        $this->assertNotEmpty($matches[0], 'the parents step must render input elements');

        foreach ($matches[0] as $tag) {
            $this->assertLessThanOrEqual(
                1,
                preg_match_all('/\bid=/', $tag),
                'input rendered more than one id attribute: '.preg_replace('/\s+/', ' ', $tag)
            );
        }
    }

    /**
     * The album form has no slug field, so 'slug' is absent from the validated
     * data. Reading it with "?:" raised "Undefined array key" and every attempt
     * to create an album returned a 500 — which made the photo upload UI on the
     * edit screen unreachable.
     */
    public function test_album_can_be_created_without_a_slug_field(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('admin.galleries.store'), [
                'title' => 'Kegiatan Sekolah',
                'sort_order' => 0,
                'is_published' => '1',
            ])
            ->assertRedirect();

        $gallery = Gallery::query()->firstOrFail();

        $this->assertSame('kegiatan-sekolah', $gallery->slug);
    }

    public function test_facility_can_be_created_without_a_slug_field(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('admin.facilities.store'), [
                'name' => 'Laboratorium Komputer',
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('laboratorium-komputer', Facility::query()->firstOrFail()->slug);
    }

    /**
     * Per-file failures land on "images.0", not "images", so the album form used
     * to swallow them and the upload looked like it silently did nothing.
     */
    public function test_oversized_photo_reports_a_readable_error_on_the_album_form(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);
        $gallery = Gallery::query()->create([
            'title' => 'Album Uji',
            'slug' => 'album-uji',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.galleries.edit', $gallery))
            ->post(route('admin.galleries.images.store', $gallery), [
                'images' => [UploadedFile::fake()->image('besar.jpg')->size(4096)],
            ])
            ->assertRedirect(route('admin.galleries.edit', $gallery))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, $gallery->images()->count());

        // The message must also be visible once the redirect is rendered. The
        // test session driver is "array", which does not carry flash data across
        // two separate requests, so the redirect is followed within one call.
        $this->actingAs($admin)
            ->from(route('admin.galleries.edit', $gallery))
            ->followingRedirects()
            ->post(route('admin.galleries.images.store', $gallery), [
                'images' => [UploadedFile::fake()->image('besar-lagi.jpg')->size(4096)],
            ])
            ->assertOk()
            ->assertSee('Ukuran setiap foto maksimal 3 MB.');
    }

    /**
     * Route::resource had generated twelve show routes for controllers that never
     * implemented show(), each a latent 500 for anyone hitting the URL directly.
     */
    public function test_every_registered_route_points_at_an_existing_controller_method(): void
    {
        $broken = [];

        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);

            if (class_exists($class) && ! method_exists($class, $method)) {
                $broken[] = ($route->getName() ?? $route->uri()).' -> '.$action;
            }
        }

        $this->assertSame([], $broken, "routes resolve to missing controller methods:\n".implode("\n", $broken));
    }
}
