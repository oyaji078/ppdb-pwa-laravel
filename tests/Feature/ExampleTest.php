<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fresh install: the database is migrated but nothing has been configured yet.
 * The public site must still render, showing empty states rather than errors.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_homepage_explains_that_no_academic_year_exists_yet(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tahun ajaran belum ditetapkan')
            ->assertSee('Pendaftaran Belum Dibuka');
    }

    public function test_public_pages_survive_an_empty_database(): void
    {
        foreach (['profile', 'programs', 'facilities', 'news.index', 'gallery.index',
            'announcements.index', 'downloads.index', 'ppdb.index', 'ppdb.schedule',
            'ppdb.requirements', 'contact'] as $name) {
            $this->get(route($name))->assertOk("Halaman {$name} gagal dirender pada database kosong.");
        }
    }

    public function test_registration_is_refused_before_anything_is_configured(): void
    {
        $this->get(route('registration.start'))
            ->assertRedirect(route('ppdb.index'));
    }
}
