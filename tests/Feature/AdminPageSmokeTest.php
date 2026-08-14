<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Download;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\News;
use App\Models\SchoolProfile;
use App\Services\RegistrationService;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every admin screen with a super admin. Catches view-level breakage
 * that the workflow tests would not reach.
 */
class AdminPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_index_page_renders(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->seed(CmsSeeder::class);

        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $routes = [
            'admin.dashboard',
            'admin.registrations.index',
            'admin.verification.index',
            'admin.selection.index',
            'admin.reregistration.index',
            'admin.reports.index',
            'admin.academic-years.index',
            'admin.waves.index',
            'admin.tracks.index',
            'admin.programs.index',
            'admin.document-types.index',
            'admin.schedules.index',
            'admin.announcements.index',
            'admin.news.index',
            'admin.galleries.index',
            'admin.facilities.index',
            'admin.downloads.index',
            'admin.school-profile.index',
            'admin.users.index',
            'admin.settings.edit',
            'admin.activity-log.index',
            'admin.profile.edit',
        ];

        foreach ($routes as $name) {
            $this->actingAs($admin)
                ->get(route($name))
                ->assertOk("Halaman {$name} gagal dirender.");
        }
    }

    public function test_every_admin_create_form_renders(): void
    {
        $this->createPpdbConfiguration();
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $routes = [
            'admin.academic-years.create',
            'admin.waves.create',
            'admin.tracks.create',
            'admin.programs.create',
            'admin.document-types.create',
            'admin.schedules.create',
            'admin.announcements.create',
            'admin.news.create',
            'admin.galleries.create',
            'admin.facilities.create',
            'admin.downloads.create',
            'admin.users.create',
        ];

        foreach ($routes as $name) {
            $this->actingAs($admin)
                ->get(route($name))
                ->assertOk("Halaman {$name} gagal dirender.");
        }
    }

    public function test_every_admin_edit_form_renders(): void
    {
        $config = $this->createPpdbConfiguration();
        $admin = $this->createAdmin(UserRole::SuperAdmin);
        $this->seed(CmsSeeder::class);

        $announcement = Announcement::query()->create([
            'title' => 'Pengumuman Uji',
            'slug' => 'pengumuman-uji',
            'content' => 'Isi pengumuman uji.',
            'audience' => 'public',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $news = News::query()->create([
            'title' => 'Berita Uji',
            'slug' => 'berita-uji',
            'content' => 'Isi berita uji.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $gallery = Gallery::query()->create([
            'title' => 'Album Uji',
            'slug' => 'album-uji',
            'is_published' => true,
        ]);

        $download = Download::query()->create([
            'title' => 'Panduan Uji',
            'file_path' => 'cms/downloads/uji.pdf',
            'original_name' => 'panduan.pdf',
            'extension' => 'pdf',
            'file_size' => 1024,
            'is_published' => true,
        ]);

        $pages = [
            route('admin.academic-years.edit', $config['year']),
            route('admin.waves.edit', $config['wave']),
            route('admin.tracks.edit', $config['track']),
            route('admin.programs.edit', $config['program']),
            route('admin.document-types.edit', $config['documentTypes']['kk']),
            route('admin.announcements.edit', $announcement),
            route('admin.news.edit', $news),
            route('admin.galleries.edit', $gallery),
            route('admin.facilities.edit', Facility::query()->firstOrFail()),
            route('admin.downloads.edit', $download),
            route('admin.users.edit', $admin),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk("Halaman {$url} gagal dirender.");
        }
    }

    public function test_registration_and_verification_detail_pages_render(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $admin = $this->createAdmin(UserRole::SuperAdmin);
        $registration = $registration->fresh();

        $this->actingAs($admin)->get(route('admin.registrations.show', $registration))->assertOk();
        $this->actingAs($admin)->get(route('admin.verification.show', $registration))->assertOk();
    }

    public function test_every_applicant_page_renders(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);
        $registration = $registration->fresh();

        $routes = [
            'applicant.dashboard',
            'applicant.biodata',
            'applicant.parents',
            'applicant.previous-school',
            'applicant.program',
            'applicant.profile',
            'applicant.documents.index',
            'applicant.announcements.index',
        ];

        foreach ($routes as $name) {
            $this->actingAsApplicant($registration)
                ->get(route($name))
                ->assertOk("Halaman {$name} gagal dirender.");
        }
    }

    public function test_every_registration_wizard_step_renders(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        $this->post(route('registration.start.store'), [
            'registration_wave_id' => $config['wave']->id,
            'admission_track_id' => $config['track']->id,
        ]);

        $routes = [
            'registration.start',
            'registration.biodata',
            'registration.address',
            'registration.parents',
            'registration.previous-school',
            'registration.program',
            'registration.documents',
            'registration.review',
        ];

        foreach ($routes as $name) {
            $this->get(route($name))->assertOk("Langkah {$name} gagal dirender.");
        }
    }

    public function test_the_school_profile_screen_lists_every_section(): void
    {
        $this->seed(CmsSeeder::class);
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $response = $this->actingAs($admin)->get(route('admin.school-profile.index'))->assertOk();

        foreach (SchoolProfile::query()->pluck('title') as $title) {
            $response->assertSee($title);
        }
    }

    public function test_the_dashboard_reports_real_counts(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();

        foreach (range(1, 3) as $ignored) {
            $registration = $this->createSubmittableDraft($config);
            app(RegistrationService::class)->submit($registration, statementAgreed: true);
        }

        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Pendaftar')
            // Three submitted registrations, counted from the database.
            ->assertSeeInOrder(['Total Pendaftar', '3']);
    }
}
