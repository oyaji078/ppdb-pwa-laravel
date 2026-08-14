<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores CMS images on the public disk with randomised filenames.
 *
 * These are genuinely public assets (news covers, facility photos, the school
 * logo), unlike applicant documents which never leave the private disk.
 */
class ImageUploader
{
    public function store(UploadedFile $file, string $folder): string
    {
        $name = Str::random(40).'.'.strtolower($file->getClientOriginalExtension());

        return $file->storeAs('cms/'.trim($folder, '/'), $name, ['disk' => 'public']);
    }

    /**
     * Store a new image and remove the one it replaces.
     */
    public function replace(UploadedFile $file, string $folder, ?string $previousPath): string
    {
        $path = $this->store($file, $folder);

        $this->delete($previousPath);

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus gambar CMS.', ['path' => $path, 'message' => $e->getMessage()]);
        }
    }
}
