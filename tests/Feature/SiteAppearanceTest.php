<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Homepage banner and school map location, both configured by a super admin.
 */
class SiteAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hero_falls_back_to_a_plain_colour_without_a_banner(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('bg-gradient-to-br', false)
            ->assertDontSee('Banner beranda', false);
    }

    public function test_a_super_admin_can_upload_a_banner_and_the_hero_uses_it(): void
    {
        Storage::fake('public');
        $this->createPpdbConfiguration();

        $this->actingAs($this->createAdmin(UserRole::SuperAdmin))
            ->put(route('admin.settings.update'), $this->settingsPayload([
                'hero_banner' => UploadedFile::fake()->image('banner.jpg', 1920, 1080),
                'hero_overlay' => '70',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = app(SettingsRepository::class);
        $settings->flush();

        $this->assertNotNull($settings->heroBannerUrl());
        $this->assertSame(0.7, $settings->heroOverlayOpacity());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($settings->heroBannerUrl(), false)
            ->assertDontSee('bg-gradient-to-br', false);
    }

    public function test_an_oversized_banner_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->createAdmin(UserRole::SuperAdmin))
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), $this->settingsPayload([
                'hero_banner' => UploadedFile::fake()->image('besar.jpg')->size(4096),
            ]))
            ->assertSessionHasErrors('hero_banner');
    }

    public function test_the_banner_can_be_removed_to_go_back_to_a_plain_colour(): void
    {
        Storage::fake('public');
        $this->createPpdbConfiguration();
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->settingsPayload([
            'hero_banner' => UploadedFile::fake()->image('banner.jpg'),
        ]));

        app(SettingsRepository::class)->flush();
        $this->assertNotNull(app(SettingsRepository::class)->heroBannerUrl());

        $this->actingAs($admin)->put(route('admin.settings.update'), $this->settingsPayload([
            'remove_hero_banner' => '1',
        ]));

        app(SettingsRepository::class)->flush();

        $this->assertNull(app(SettingsRepository::class)->heroBannerUrl());
        $this->get(route('home'))->assertOk()->assertSee('bg-gradient-to-br', false);
    }

    /**
     * The overlay is clamped so an operator cannot make hero text unreadable by
     * typing 0, nor hide the banner entirely with 100.
     */
    public function test_the_overlay_opacity_stays_within_a_usable_range(): void
    {
        $settings = app(SettingsRepository::class);

        $settings->setMany(['hero_overlay' => '150'], 'homepage');
        $settings->flush();
        $this->assertSame(0.9, $settings->heroOverlayOpacity());

        $settings->setMany(['hero_overlay' => '-20'], 'homepage');
        $settings->flush();
        $this->assertSame(0.0, $settings->heroOverlayOpacity());
    }

    // -- Map ------------------------------------------------------------------

    public function test_the_contact_page_has_no_map_until_coordinates_are_set(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertDontSee('Lokasi Sekolah')
            ->assertDontSee('google.com/maps', false);
    }

    public function test_a_super_admin_can_set_the_school_location(): void
    {
        $this->actingAs($this->createAdmin(UserRole::SuperAdmin))
            ->put(route('admin.settings.update'), $this->settingsPayload([
                'map_latitude' => '-8.652900',
                'map_longitude' => '116.542100',
                'map_zoom' => '17',
                'map_place_name' => 'SMA Islam Hamzanwadi Peneda',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = app(SettingsRepository::class);
        $settings->flush();

        $this->assertTrue($settings->hasMapLocation());
        $this->assertStringContainsString('-8.652900,116.542100', $settings->mapEmbedUrl());
        $this->assertStringContainsString('output=embed', $settings->mapEmbedUrl());
        $this->assertStringContainsString('z=17', $settings->mapEmbedUrl());

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Lokasi Sekolah')
            ->assertSee('SMA Islam Hamzanwadi Peneda')
            ->assertSee('Buka di Google Maps')
            ->assertSee('output=embed', false);
    }

    public function test_impossible_coordinates_are_refused(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), $this->settingsPayload(['map_latitude' => '120']))
            ->assertSessionHasErrors('map_latitude');

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), $this->settingsPayload(['map_longitude' => 'bukan-angka']))
            ->assertSessionHasErrors('map_longitude');
    }

    /**
     * Half a coordinate pair is not a location, and must not render a map
     * pointing at the wrong place.
     */
    public function test_a_half_filled_coordinate_pair_draws_no_map(): void
    {
        $settings = app(SettingsRepository::class);

        $settings->setMany(['map_latitude' => '-8.65', 'map_longitude' => ''], 'location');
        $settings->flush();

        $this->assertFalse($settings->hasMapLocation());
        $this->assertNull($settings->mapEmbedUrl());
        $this->get(route('contact'))->assertOk()->assertDontSee('Lokasi Sekolah');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'SMA Islam Hamzanwadi Peneda',
            'admission_name' => 'PPDB',
            'mail_mailer' => 'smtp',
        ], $overrides);
    }
}
