<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\RegistrationDocument;
use App\Services\DocumentService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_pdf_is_accepted_and_stored_privately(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $document = app(DocumentService::class)->store(
            $registration,
            $config['documentTypes']['kk'],
            UploadedFile::fake()->create('kartu-keluarga.pdf', 200, 'application/pdf')
        );

        $this->assertSame('pdf', $document->extension);
        $this->assertSame(DocumentStatus::Pending, $document->verification_status);
        $this->assertSame('kartu-keluarga.pdf', $document->original_name);

        Storage::disk(config('ppdb.storage.disk'))->assertExists($document->storage_path);

        // Stored under the private ppdb tree, with a randomised filename.
        $this->assertStringStartsWith('ppdb/', $document->storage_path);
        $this->assertStringNotContainsString('kartu-keluarga', $document->stored_name);
        $this->assertSame(44, strlen($document->stored_name), 'nama file acak 40 karakter + ekstensi');
    }

    public function test_a_disallowed_extension_is_rejected(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Format berkas tidak didukung');

        app(DocumentService::class)->store(
            $registration,
            // This type only allows PDF.
            $config['documentTypes']['sertifikat'],
            UploadedFile::fake()->image('prestasi.jpg')
        );
    }

    public function test_a_file_larger_than_the_type_limit_is_rejected(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $config['documentTypes']['kk']->update(['max_size_kb' => 500]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Ukuran berkas maksimal');

        app(DocumentService::class)->store(
            $registration,
            $config['documentTypes']['kk']->fresh(),
            UploadedFile::fake()->create('besar.pdf', 900, 'application/pdf')
        );
    }

    public function test_a_mismatched_mime_type_is_rejected(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        // Extension says PDF, content says something else: a rename attack.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Isi berkas tidak sesuai');

        app(DocumentService::class)->store(
            $registration,
            $config['documentTypes']['sertifikat'],
            UploadedFile::fake()->create('palsu.pdf', 100, 'text/html')
        );
    }

    public function test_re_uploading_replaces_the_previous_file(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $service = app(DocumentService::class);
        $disk = Storage::disk(config('ppdb.storage.disk'));

        $first = $service->store($registration, $config['documentTypes']['kk'],
            UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf'));
        $firstPath = $first->storage_path;

        $second = $service->store($registration->fresh(), $config['documentTypes']['kk'],
            UploadedFile::fake()->create('baru.pdf', 120, 'application/pdf'));

        $this->assertSame($first->id, $second->id, 'satu baris per jenis berkas');
        $this->assertSame('baru.pdf', $second->original_name);
        $disk->assertMissing($firstPath);
        $disk->assertExists($second->storage_path);
        $this->assertSame(1, RegistrationDocument::query()->count());
    }

    public function test_a_verified_document_cannot_be_replaced(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $service = app(DocumentService::class);

        $document = $service->store($registration, $config['documentTypes']['kk'],
            UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'));

        $document->forceFill(['verification_status' => DocumentStatus::Verified])->save();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sudah diverifikasi');

        $service->store($registration->fresh(), $config['documentTypes']['kk'],
            UploadedFile::fake()->create('kk-baru.pdf', 100, 'application/pdf'));
    }

    /**
     * The registration number is issued when the account is opened, so uploads
     * land in their final folder immediately — there is no longer a draft
     * folder for them to be moved out of on submission.
     */
    public function test_files_are_stored_under_the_registration_number_from_the_start(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);

        $this->assertNotNull($registration->registration_number);

        foreach ($registration->documents as $document) {
            $this->assertStringContainsString($registration->registration_number, $document->storage_path);
            $this->assertStringNotContainsString('draft-', $document->storage_path);
        }

        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $registration->refresh()->load('documents');
        $disk = Storage::disk(config('ppdb.storage.disk'));

        // Submission must leave the paths untouched and the files in place.
        foreach ($registration->documents as $document) {
            $this->assertStringContainsString($registration->registration_number, $document->storage_path);
            $this->assertStringNotContainsString('draft-', $document->storage_path);
            $disk->assertExists($document->storage_path);
        }
    }

    /**
     * The upload page sends the files one at a time from a single button press
     * and reads the outcome of each. Without a JSON answer it would have to
     * parse a redirect, so this contract is what the page depends on.
     */
    public function test_an_upload_answers_with_json_when_the_page_asks_for_it(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $response = $this->actingAsApplicant($registration->fresh())
            ->postJson(route('registration.documents.store', $config['documentTypes']['kk']), [
                'file' => UploadedFile::fake()->create('kartu-keluarga.pdf', 200, 'application/pdf'),
            ]);

        $response->assertOk()
            ->assertJsonPath('document.name', 'kartu-keluarga.pdf')
            ->assertJsonStructure(['message', 'document' => ['name', 'size']]);

        $this->assertDatabaseCount('registration_documents', 1);
    }

    /**
     * A rejected file has to come back as JSON too, and name the reason, or the
     * page cannot tell the applicant which row to fix.
     */
    public function test_a_rejected_upload_answers_with_a_json_error(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createAccountFor($config);

        $this->actingAsApplicant($registration->fresh())
            ->postJson(route('registration.documents.store', $config['documentTypes']['sertifikat']), [
                // That type takes PDF only.
                'file' => UploadedFile::fake()->image('prestasi.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('registration_documents', 0);
    }
}
