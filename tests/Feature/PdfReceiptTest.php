<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\Registration;
use App\Services\PdfService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_receipt_is_generated_on_submission(): void
    {
        $registration = $this->submitted();

        $receipt = GeneratedDocument::query()
            ->where('registration_id', $registration->id)
            ->where('type', GeneratedDocument::TYPE_RECEIPT)
            ->firstOrFail();

        $disk = Storage::disk(config('ppdb.storage.disk'));

        $disk->assertExists($receipt->storage_path);
        $this->assertNotNull($receipt->checksum);
        $this->assertNotNull($receipt->generated_at);
        $this->assertStringStartsWith('%PDF', $disk->get($receipt->storage_path));
    }

    public function test_the_receipt_filename_uses_the_registration_number(): void
    {
        $registration = $this->submitted();

        $this->assertSame(
            'Bukti-Pendaftaran-'.$registration->registration_number.'.pdf',
            app(PdfService::class)->receiptFileName($registration)
        );
    }

    public function test_the_receipt_is_stored_on_private_storage_only(): void
    {
        $registration = $this->submitted();

        $receipt = $registration->generatedDocuments()->firstOrFail();

        $this->assertStringStartsWith('ppdb/', $receipt->storage_path);
        $this->assertFalse(Storage::disk('public')->exists($receipt->storage_path));
        $this->get('/storage/'.$receipt->storage_path)->assertNotFound();
    }

    public function test_the_qr_code_points_at_the_status_page_without_the_access_code(): void
    {
        $registration = $this->submitted();

        $url = app(PdfService::class)->statusUrl($registration);

        $this->assertStringContainsString('cek-status', $url);
        $this->assertStringContainsString($registration->registration_number, $url);
        $this->assertStringNotContainsString('access_code', $url);
        $this->assertStringNotContainsString('kode', $url);
    }

    public function test_the_applicant_can_download_their_receipt(): void
    {
        $registration = $this->submitted();

        $response = $this->actingAsApplicant($registration)
            ->get(route('applicant.receipt'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'Bukti-Pendaftaran-'.$registration->registration_number.'.pdf',
            $response->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_a_missing_receipt_file_is_regenerated_on_download(): void
    {
        $registration = $this->submitted();
        $disk = Storage::disk(config('ppdb.storage.disk'));

        $receipt = $registration->generatedDocuments()->firstOrFail();
        $disk->delete($receipt->storage_path);
        $disk->assertMissing($receipt->storage_path);

        $this->actingAsApplicant($registration)
            ->get(route('applicant.receipt'))
            ->assertOk();

        $disk->assertExists($registration->fresh()->generatedDocuments()->firstOrFail()->storage_path);
    }

    public function test_an_admin_can_download_an_applicants_receipt(): void
    {
        $registration = $this->submitted();

        $this->actingAs($this->createAdmin())
            ->get(route('admin.registrations.receipt', $registration))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_a_guest_cannot_download_a_receipt(): void
    {
        $registration = $this->submitted();

        $this->get(route('applicant.receipt'))->assertRedirect(route('status.form'));
        $this->get(route('admin.registrations.receipt', $registration))->assertRedirect(route('admin.login'));
    }

    public function test_the_receipt_records_a_checksum_of_its_contents(): void
    {
        $registration = $this->submitted();
        $receipt = $registration->generatedDocuments()->firstOrFail();

        $stored = Storage::disk(config('ppdb.storage.disk'))->get($receipt->storage_path);

        $this->assertSame(hash('sha256', $stored), $receipt->checksum);
    }

    private function submitted(): Registration
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        return $registration->fresh();
    }
}
