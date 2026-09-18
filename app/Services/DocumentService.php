<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\DocumentType;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores applicant uploads on the private disk and keeps the database row in
 * step with what is actually on disk.
 *
 * Layout: ppdb/{intake year}/{registration number}/{document code}/{random}.{ext}
 * While the wizard is still a draft the folder is draft-{id}; it is renamed to
 * the registration number once one has been issued.
 */
class DocumentService
{
    public function __construct(private readonly NotificationService $notifications) {}

    private function disk(): Filesystem
    {
        return Storage::disk(config('ppdb.storage.disk'));
    }

    /**
     * Validate and store an upload, replacing any previous file for the same
     * document type.
     *
     * @throws ValidationException when the file fails a server-side check
     */
    public function store(Registration $registration, DocumentType $type, UploadedFile $file): RegistrationDocument
    {
        $this->assertValidFile($type, $file);

        $existing = $registration->documents()
            ->where('document_type_id', $type->id)
            ->first();

        if ($existing !== null && ! $existing->verification_status->isReplaceable()) {
            throw ValidationException::withMessages([
                'file' => 'Berkas ini sudah diverifikasi dan tidak dapat diganti.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::random(40).'.'.$extension;
        $directory = $this->directoryFor($registration, $type);

        $path = $file->storeAs($directory, $storedName, [
            'disk' => config('ppdb.storage.disk'),
        ]);

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'Berkas gagal disimpan. Silakan coba lagi.',
            ]);
        }

        $previousPath = $existing?->storage_path;

        $document = $registration->documents()->updateOrCreate(
            ['document_type_id' => $type->id],
            [
                'original_name' => substr($file->getClientOriginalName(), 0, 255),
                'stored_name' => $storedName,
                'storage_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => $extension,
                'file_size' => $file->getSize() ?: 0,
                'verification_status' => DocumentStatus::Pending,
                'verification_note' => null,
                'uploaded_at' => now(),
                'verified_at' => null,
                'verified_by' => null,
            ]
        );

        if ($previousPath !== null && $previousPath !== $path) {
            $this->deleteFile($previousPath);
        }

        return $document;
    }

    /**
     * Server-side gate. JavaScript checks are a convenience only; this is the
     * check that counts.
     *
     * @throws ValidationException
     */
    public function assertValidFile(DocumentType $type, UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'Berkas gagal diunggah. Silakan coba lagi.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = $type->extensions();

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => sprintf('Format berkas tidak didukung. Format yang diizinkan: %s.', strtoupper(implode(', ', $allowed))),
            ]);
        }

        $mime = $file->getMimeType();

        if (! in_array($mime, $type->mimeTypes(), true)) {
            throw ValidationException::withMessages([
                'file' => 'Isi berkas tidak sesuai dengan formatnya. Silakan unggah berkas yang benar.',
            ]);
        }

        $maxBytes = $type->maxSizeKb() * 1024;

        if (($file->getSize() ?: 0) > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => sprintf('Ukuran berkas maksimal %s.', $type->humanMaxSize()),
            ]);
        }
    }

    /**
     * Remove a document row and its file.
     */
    public function delete(RegistrationDocument $document): void
    {
        $this->deleteFile($document->storage_path);
        $document->delete();
    }

    /**
     * Move a submitted registration's files from the draft folder into the
     * folder named after its registration number.
     *
     * Runs after the submit transaction commits. Each file is copied, its row
     * updated, then the old copy removed, so an interruption can only leave a
     * harmless duplicate — never a database row pointing at a missing file.
     */
    public function relocateToRegistrationNumber(Registration $registration): void
    {
        if ($registration->registration_number === null) {
            return;
        }

        $disk = $this->disk();

        foreach ($registration->documents()->with('documentType')->get() as $document) {
            $target = $this->directoryFor($registration, $document->documentType).'/'.$document->stored_name;

            if ($document->storage_path === $target) {
                continue;
            }

            try {
                if (! $disk->exists($document->storage_path)) {
                    continue;
                }

                $disk->copy($document->storage_path, $target);

                $oldPath = $document->storage_path;
                $document->forceFill(['storage_path' => $target])->save();

                $disk->delete($oldPath);
            } catch (\Throwable $e) {
                Log::warning('Gagal memindahkan berkas pendaftaran.', [
                    'document_id' => $document->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Directory for a registration's files of a given document type.
     */
    public function directoryFor(Registration $registration, DocumentType $type): string
    {
        $year = $registration->academicYear?->start_year ?? now()->year;
        $folder = $registration->registration_number ?? 'draft-'.$registration->id;

        return sprintf(
            '%s/%s/%s/%s',
            config('ppdb.storage.root_folder'),
            $year,
            $folder,
            Str::slug($type->code) ?: 'lainnya'
        );
    }

    /**
     * Stream a private document through the authorized application route.
     *
     * Using the filesystem response keeps this compatible with both a local
     * disk and object storage such as Vercel Blob, where no local path exists.
     */
    public function response(RegistrationDocument $document, string $disposition = 'inline'): ?StreamedResponse
    {
        $disk = $this->disk();

        if (! $disk->exists($document->storage_path)) {
            return null;
        }

        return $disk->response(
            $document->storage_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'Cache-Control' => 'no-store, private',
            ],
            $disposition,
        );
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    private function deleteFile(string $path): void
    {
        try {
            $this->disk()->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus berkas lama.', ['path' => $path, 'message' => $e->getMessage()]);
        }
    }
}
