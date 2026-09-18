<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Creating an album and putting photos in it.
 *
 * Photos belong to an album, so they can only be stored once one exists. That
 * constraint used to be pushed onto the operator, who had to save an album and
 * then find their way to a second screen to add anything to it.
 */
class GalleryAlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_new_album_form_offers_a_photo_picker(): void
    {
        $html = $this->actingAs($this->createAdmin())
            ->get(route('admin.galleries.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="images[]"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
    }

    /**
     * The no-JavaScript path: album and photos arrive in one request.
     */
    public function test_an_album_can_be_created_with_photos_in_one_request(): void
    {
        Storage::fake('public');

        $this->actingAs($this->createAdmin())
            ->post(route('admin.galleries.store'), [
                'title' => 'Kegiatan Sekolah',
                'sort_order' => 0,
                'is_published' => '1',
                'caption' => 'Upacara bendera',
                'images' => [
                    UploadedFile::fake()->image('satu.jpg'),
                    UploadedFile::fake()->image('dua.jpg'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $gallery = Gallery::query()->firstOrFail();

        $this->assertSame('Kegiatan Sekolah', $gallery->title);
        $this->assertCount(2, $gallery->images);
        $this->assertSame('Upacara bendera', $gallery->images->first()->caption);

        foreach ($gallery->images as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
    }

    public function test_an_album_can_still_be_created_without_any_photo(): void
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.galleries.store'), [
                'title' => 'Album Kosong',
                'sort_order' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Gallery::query()->firstOrFail()->images()->count());
    }

    /**
     * The page creates the album over fetch before sending photos, so it needs
     * the new id and somewhere to go afterwards.
     */
    public function test_creating_an_album_answers_with_json_when_asked(): void
    {
        $response = $this->actingAs($this->createAdmin())
            ->postJson(route('admin.galleries.store'), [
                'title' => 'Album JSON',
                'sort_order' => 0,
            ]);

        $response->assertCreated()->assertJsonStructure(['id', 'editUrl']);

        $this->assertSame(
            route('admin.galleries.edit', Gallery::query()->firstOrFail()),
            $response->json('editUrl')
        );
    }

    /**
     * Photos are sent one per request, so the answer has to say whether that one
     * landed rather than redirecting.
     */
    public function test_adding_a_photo_answers_with_json_when_asked(): void
    {
        Storage::fake('public');

        $gallery = Gallery::query()->create([
            'title' => 'Album Uji',
            'slug' => 'album-uji',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->createAdmin())
            ->postJson(route('admin.galleries.images.store', $gallery), [
                'images' => [UploadedFile::fake()->image('foto.jpg')],
            ])
            ->assertOk()
            ->assertJsonPath('saved', 1);

        $this->assertSame(1, $gallery->refresh()->images()->count());
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->createAdmin())
            ->from(route('admin.galleries.create'))
            ->post(route('admin.galleries.store'), [
                'title' => 'Album Salah',
                'sort_order' => 0,
                'images' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, Gallery::query()->count());
    }

    public function test_gallery_lightbox_serializes_image_urls_safely(): void
    {
        Storage::fake('public');

        $gallery = Gallery::query()->create([
            'title' => 'Album Publik',
            'slug' => 'album-publik',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        GalleryImage::query()->create([
            'gallery_id' => $gallery->id,
            'image_path' => "cms/gallery/foto'khusus.jpg",
            'sort_order' => 1,
        ]);

        $html = $this->get(route('gallery.show', $gallery))->assertOk()->getContent();

        $this->assertStringContainsString('foto\\u0027khusus.jpg', $html);
        $this->assertStringNotContainsString("active = '/storage/cms/gallery/foto'khusus.jpg'", $html);
    }
}
