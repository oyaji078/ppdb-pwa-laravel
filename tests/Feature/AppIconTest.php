<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The favicon and installed-app icons, rendered on demand from the school's
 * uploaded logo.
 */
class AppIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_offered_size_renders_a_png_of_that_size(): void
    {
        foreach ([32, 48, 180, 192, 512] as $size) {
            $body = $this->get('/app-icon/'.$size.'.png')
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png')
                ->getContent();

            $dimensions = getimagesizefromstring($body);

            $this->assertNotFalse($dimensions, "ukuran {$size} tidak menghasilkan gambar");
            $this->assertSame([$size, $size], [$dimensions[0], $dimensions[1]]);
        }
    }

    public function test_favicon_route_returns_a_real_icon(): void
    {
        $body = $this->get('/favicon.ico')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->getContent();

        $dimensions = getimagesizefromstring($body);

        $this->assertNotFalse($dimensions, 'favicon tidak boleh kosong');
        $this->assertSame([32, 32], [$dimensions[0], $dimensions[1]]);
    }

    /**
     * The size list is closed so that a caller cannot make the server rasterise
     * arbitrary dimensions on demand.
     */
    public function test_a_size_that_is_not_offered_is_refused(): void
    {
        $this->get('/app-icon/999.png')->assertNotFound();
        $this->get('/app-icon/193.png')->assertNotFound();
    }

    public function test_the_maskable_variant_is_rendered_separately(): void
    {
        $plain = $this->get('/app-icon/512.png')->getContent();
        $maskable = $this->get('/app-icon/512.png?maskable=1')->getContent();

        $this->assertNotSame($plain, $maskable, 'varian maskable seharusnya berbeda');
    }

    /**
     * The rendered PNG is cached, and the cache store is a database column
     * holding text. Raw image bytes cannot go in one: MySQL refuses the write
     * outright, which turned every icon request into a 500. The array store
     * used here would accept anything, so the guard has to be on what is
     * written rather than on the store rejecting it.
     */
    public function test_what_is_cached_can_be_stored_in_a_text_column(): void
    {
        $this->get('/app-icon/192.png')->assertOk();

        $keys = array_keys(Cache::getStore()->all() ?? []);
        $iconKeys = array_values(array_filter($keys, fn (string $key) => str_contains($key, 'app-icon')));

        $this->assertNotEmpty($iconKeys, 'ikon tidak tersimpan di cache');

        foreach ($iconKeys as $key) {
            $cached = Cache::get(str_replace(config('cache.prefix'), '', $key));

            $this->assertIsString($cached);
            $this->assertStringNotContainsString("\0", $cached, 'nilai cache memuat byte NUL');
            $this->assertMatchesRegularExpression('//u', $cached, 'nilai cache bukan UTF-8 yang sah');
            $this->assertNotFalse(base64_decode($cached, true), 'nilai cache bukan base64');
        }
    }
}
