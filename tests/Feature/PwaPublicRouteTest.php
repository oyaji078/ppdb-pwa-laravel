<?php

namespace Tests\Feature;

use App\Services\RegistrationService;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaPublicRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_responds(): void
    {
        $this->seedBaseline();

        $routes = [
            'home', 'profile', 'programs', 'facilities', 'contact',
            'news.index', 'gallery.index', 'announcements.index', 'downloads.index',
            'ppdb.index', 'ppdb.schedule', 'ppdb.requirements',
            'login', 'pwa.offline',
        ];

        foreach ($routes as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_the_manifest_is_served_with_the_right_content_type(): void
    {
        $response = $this->get(route('pwa.manifest'))->assertOk();

        $this->assertStringContainsString('application/manifest+json', $response->headers->get('Content-Type'));

        $manifest = $response->json();

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertNotEmpty($manifest['icons']);
        $this->assertSame('192x192', $manifest['icons'][0]['sizes']);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));
    }

    public function test_the_manifest_uses_the_configured_school_name(): void
    {
        settings()->set('school_name', 'SMA Contoh Uji', 'school');
        settings()->set('admission_name', 'PMBM', 'admission');

        $manifest = $this->get(route('pwa.manifest'))->assertOk()->json();

        $this->assertStringContainsString('SMA Contoh Uji', $manifest['name']);
        $this->assertStringContainsString('PMBM', $manifest['name']);
    }

    public function test_the_service_worker_is_javascript_and_never_cached(): void
    {
        $response = $this->get(route('pwa.service-worker'))->assertOk();

        $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('/', $response->headers->get('Service-Worker-Allowed'));
    }

    public function test_the_service_worker_refuses_to_cache_private_areas(): void
    {
        $body = $this->get(route('pwa.service-worker'))->assertOk()->getContent();

        $this->assertStringContainsString("'/admin'", $body);
        $this->assertStringContainsString("'/pendaftar'", $body);
        $this->assertStringContainsString('PRIVATE_PREFIXES', $body);
        $this->assertStringContainsString('isPrivate(url)', $body);
    }

    public function test_the_service_worker_precaches_only_public_pages(): void
    {
        $body = $this->get(route('pwa.service-worker'))->assertOk()->getContent();

        preg_match('/const PRECACHE_URLS = (\[.*?\]);/s', $body, $matches);

        $this->assertNotEmpty($matches, 'daftar precache harus ada di service worker');

        $urls = json_decode($matches[1], true);

        $this->assertContains('/', $urls);
        $this->assertContains('/offline', $urls);

        foreach ($urls as $url) {
            $this->assertStringStartsNotWith('/admin', $url);
            $this->assertStringStartsNotWith('/pendaftar', $url);
        }

        $this->assertStringContainsString("new Request(url, { credentials: 'omit' })", $body);
    }

    public function test_the_offline_page_explains_the_situation(): void
    {
        $this->get(route('pwa.offline'))
            ->assertOk()
            ->assertSee('Anda sedang offline')
            ->assertSee('membutuhkan koneksi internet');
    }

    public function test_public_pages_are_cacheable_but_private_pages_are_not(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $public = $this->get(route('home'));
        $this->assertStringNotContainsString('no-store', (string) $public->headers->get('Cache-Control'));

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.dashboard'))
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function test_public_pages_link_the_manifest(): void
    {
        $this->seedBaseline();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false);
    }

    public function test_error_pages_render(): void
    {
        // A 404 must render the friendly page, not a stack trace.
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }

    private function seedBaseline(): void
    {
        $this->createPpdbConfiguration();
        $this->seed(CmsSeeder::class);
    }
}
