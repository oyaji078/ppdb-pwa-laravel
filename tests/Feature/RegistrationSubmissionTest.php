<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\GeneratedDocument;
use App\Models\Notification;
use App\Models\Registration;
use App\Services\AccessCodeService;
use App\Services\PdfService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Credentials are issued when the account is opened, so submission only
     * advances the status and must leave the number and code untouched.
     */
    public function test_a_complete_draft_can_be_submitted_and_keeps_its_credentials(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $numberBefore = $draft->registration_number;
        $hashBefore = $draft->user->password;

        $this->assertNotNull($numberBefore, 'nomor pendaftaran terbit saat akun dibuat');

        app(RegistrationService::class)->submit($draft, statementAgreed: true);

        $draft->refresh();

        $this->assertSame(RegistrationStatus::Submitted, $draft->registration_status);
        $this->assertNotNull($draft->submitted_at);
        $this->assertTrue($draft->statement_agreed);
        $this->assertSame($numberBefore, $draft->registration_number);
        $this->assertSame($hashBefore, $draft->user->password);
        $this->assertTrue(app(AccessCodeService::class)->check($draft, self::ACCESS_CODE));
    }

    public function test_the_password_is_only_stored_as_a_hash(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        app(RegistrationService::class)->submit($draft, statementAgreed: true);

        $stored = Registration::query()->find($draft->id)->user->password;

        $this->assertNotSame(self::ACCESS_CODE, $stored);
        $this->assertStringNotContainsString(self::ACCESS_CODE, $stored);
        $this->assertDatabaseMissing('users', ['password' => self::ACCESS_CODE]);
    }

    public function test_the_access_code_only_uses_unambiguous_characters(): void
    {
        $service = app(AccessCodeService::class);

        for ($i = 0; $i < 200; $i++) {
            $code = $service->generate();

            $this->assertSame(8, strlen($code));
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/', $code);
        }
    }

    public function test_submission_generates_a_receipt_and_a_notification(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        app(RegistrationService::class)->submit($draft, statementAgreed: true);

        $draft->refresh();

        $receipt = GeneratedDocument::query()
            ->where('registration_id', $draft->id)
            ->where('type', GeneratedDocument::TYPE_RECEIPT)
            ->first();

        $this->assertNotNull($receipt);
        $this->assertSame($draft->registration_number, $receipt->document_number);
        Storage::disk(config('ppdb.storage.disk'))->assertExists($receipt->storage_path);

        $this->assertDatabaseHas('notifications', [
            'registration_id' => $draft->id,
            'type' => Notification::TYPE_REGISTRATION_SUBMITTED,
        ]);
    }

    public function test_submission_is_rejected_without_the_truth_statement(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $this->expectException(ValidationException::class);

        try {
            app(RegistrationService::class)->submit($draft, statementAgreed: false);
        } finally {
            $this->assertNull($draft->fresh()->submitted_at);
            $this->assertSame(RegistrationStatus::Draft, $draft->fresh()->registration_status);
        }
    }

    public function test_submission_is_rejected_when_a_required_document_is_missing(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $draft->documents()->where('document_type_id', $config['documentTypes']['kk']->id)->delete();
        $draft->refresh();

        $this->expectException(ValidationException::class);

        try {
            app(RegistrationService::class)->submit($draft, statementAgreed: true);
        } finally {
            $this->assertNull($draft->fresh()->submitted_at);
        }
    }

    public function test_submission_is_rejected_when_biodata_is_incomplete(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $draft->applicant->update(['nisn' => null]);
        $draft->refresh();

        $this->expectException(ValidationException::class);

        app(RegistrationService::class)->submit($draft, statementAgreed: true);
    }

    public function test_a_registration_cannot_be_submitted_twice(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $service = app(RegistrationService::class);
        $service->submit($draft, statementAgreed: true);

        $number = $draft->fresh()->registration_number;

        $this->expectException(ValidationException::class);

        try {
            $service->submit($draft->fresh(), statementAgreed: true);
        } finally {
            $this->assertSame($number, $draft->fresh()->registration_number);
            $this->assertSame(1, Registration::query()->whereNotNull('submitted_at')->count());
        }
    }

    public function test_no_partial_record_survives_a_failed_submission(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        // Force the PDF step to blow up mid-transaction.
        $this->mock(PdfService::class, function ($mock): void {
            $mock->shouldReceive('generateReceipt')->andThrow(new \RuntimeException('gagal membuat PDF'));
        });

        try {
            app(RegistrationService::class)->submit($draft, statementAgreed: true);
            $this->fail('Pengiriman seharusnya gagal.');
        } catch (\RuntimeException) {
            // expected
        }

        $draft->refresh();

        // The credentials predate this transaction and must survive it; what a
        // rollback has to undo is the status change and its side effects.
        $this->assertNull($draft->submitted_at);
        $this->assertSame(RegistrationStatus::Draft, $draft->registration_status);
        $this->assertFalse((bool) $draft->statement_agreed);
        $this->assertDatabaseCount('notifications', 0);
    }
}
