<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\RegistrationDocument;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_open_an_applicant_document(): void
    {
        $document = $this->submittedDocument();

        $this->get(route('admin.documents.preview', $document))->assertRedirect(route('login'));
        $this->get(route('admin.documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_every_admin_role_may_read_documents(): void
    {
        $document = $this->submittedDocument();

        foreach ([UserRole::SuperAdmin, UserRole::AdminPpdb, UserRole::Verifier] as $role) {
            $this->actingAs($this->createAdmin($role))
                ->get(route('admin.documents.preview', $document))
                ->assertOk();
        }
    }

    public function test_documents_are_served_with_no_store_headers(): void
    {
        $document = $this->submittedDocument();

        $this->actingAs($this->createAdmin())
            ->get(route('admin.documents.download', $document))
            ->assertOk()
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function test_document_preview_streams_the_stored_file_contents(): void
    {
        $document = $this->submittedDocument();
        Storage::disk(config('ppdb.storage.disk'))->put($document->storage_path, '%PDF-admin-preview');

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.documents.preview', $document))
            ->assertOk()
            ->assertHeaderContains('Content-Disposition', 'inline');

        $this->assertSame('%PDF-admin-preview', $response->streamedContent());
    }

    public function test_uploaded_files_are_not_reachable_over_the_public_disk(): void
    {
        $document = $this->submittedDocument();

        // No public URL may exist for a private document.
        $this->assertStringStartsWith('ppdb/', $document->storage_path);
        $this->assertFalse(Storage::disk('public')->exists($document->storage_path));

        // And the storage path is not guessable through the public storage route.
        $this->get('/storage/'.$document->storage_path)->assertNotFound();
    }

    public function test_a_missing_file_returns_not_found_rather_than_an_error(): void
    {
        $document = $this->submittedDocument();

        Storage::disk(config('ppdb.storage.disk'))->delete($document->storage_path);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.documents.preview', $document))
            ->assertNotFound();
    }

    public function test_a_verifier_cannot_reset_an_access_code(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->post(route('admin.registrations.reset-code', $registration->fresh()), [
                'reason' => 'Pendaftar lupa kode akses.',
            ])
            ->assertForbidden();
    }

    public function test_only_a_super_admin_may_archive_a_registration(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);
        $registration->refresh();

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->delete(route('admin.registrations.destroy', $registration))
            ->assertForbidden();

        $this->actingAs($this->createAdmin(UserRole::AdminPpdb))
            ->delete(route('admin.registrations.destroy', $registration))
            ->assertForbidden();

        $this->actingAs($this->createAdmin(UserRole::SuperAdmin))
            ->delete(route('admin.registrations.destroy', $registration))
            ->assertRedirect(route('admin.registrations.index'));

        $this->assertSoftDeleted('registrations', ['id' => $registration->id]);
    }

    public function test_a_super_admin_can_find_and_archive_an_unfinished_draft(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createAccountFor($config, [
            'full_name' => 'Pendaftar Draft Uji',
            'nisn' => '0098765432',
        ]);

        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['q' => '0098765432']))
            ->assertOk()
            ->assertSee('Pendaftar Draft Uji')
            ->assertSee('Belum dikirim');

        $this->actingAs($admin)
            ->get(route('admin.registrations.show', $draft))
            ->assertOk()
            ->assertDontSee('Bukti Pendaftaran');

        $this->actingAs($admin)
            ->delete(route('admin.registrations.destroy', $draft))
            ->assertRedirect(route('admin.registrations.index'));

        $this->assertSoftDeleted('registrations', ['id' => $draft->id]);
    }

    private function submittedDocument(): RegistrationDocument
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        return $registration->fresh()->documents()->firstOrFail();
    }
}
