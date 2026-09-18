<?php

namespace Tests\Feature;

use App\Models\Download;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_download_larger_than_the_vercel_request_limit_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->createAdmin())
            ->from(route('admin.downloads.create'))
            ->post(route('admin.downloads.store'), [
                'title' => 'Panduan Besar',
                'file' => UploadedFile::fake()->create('panduan.pdf', 5120, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.downloads.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('downloads', 0);
    }

    public function test_a_download_within_the_vercel_request_limit_can_be_stored(): void
    {
        Storage::fake('public');

        $this->actingAs($this->createAdmin())
            ->post(route('admin.downloads.store'), [
                'title' => 'Panduan PPDB',
                'is_published' => '1',
                'file' => UploadedFile::fake()->create('panduan.pdf', 4096, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.downloads.index'));

        $download = Download::query()->firstOrFail();

        Storage::disk('public')->assertExists($download->file_path);
    }
}
