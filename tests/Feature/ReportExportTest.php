<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Registration;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_csv_export_contains_the_data_rows(): void
    {
        [$admin, $registration] = $this->scenario();

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['format' => 'csv', 'preset' => 'semua']))
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'text/csv')
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'CSV harus diawali BOM agar Excel membaca UTF-8');
        $this->assertStringContainsString('Nomor Pendaftaran', $content);
        $this->assertStringContainsString($registration->registration_number, $content);
        $this->assertStringContainsString($registration->applicant->full_name, $content);
    }

    public function test_the_xlsx_export_produces_a_valid_archive(): void
    {
        [$admin] = $this->scenario();

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['format' => 'xlsx', 'preset' => 'semua']))
            ->assertOk()
            ->streamedContent();

        // XLSX is a zip archive; anything else means the writer failed.
        $this->assertStringStartsWith('PK', $content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    public function test_the_pdf_export_produces_a_pdf(): void
    {
        [$admin] = $this->scenario();

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['format' => 'pdf', 'preset' => 'semua']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_an_unknown_format_is_refused(): void
    {
        [$admin] = $this->scenario();

        $this->actingAs($admin)
            ->from(route('admin.reports.index'))
            ->get(route('admin.reports.export', ['format' => 'docx', 'preset' => 'semua']))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('error');
    }

    public function test_an_unpublished_selection_result_is_not_leaked_by_an_export(): void
    {
        [$admin, $registration] = $this->scenario();

        // Verify then decide without publishing.
        foreach ($registration->documents as $document) {
            $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
                'verification_status' => 'verified',
            ]);
        }

        $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

        $this->actingAs($admin)->post(route('admin.selection.store', $registration->fresh()), [
            'status' => 'accepted',
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['format' => 'csv', 'preset' => 'semua']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Belum diumumkan', $content);
        $this->assertStringNotContainsString('Diterima', $content);
    }

    public function test_exporting_is_written_to_the_activity_log(): void
    {
        [$admin] = $this->scenario();

        $this->actingAs($admin)
            ->get(route('admin.reports.export', ['format' => 'csv', 'preset' => 'semua']))
            ->streamedContent();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityLogger::REPORT_EXPORTED,
        ]);
    }

    /**
     * @return array{0: User, 1: Registration}
     */
    private function scenario(): array
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        return [$this->createAdmin(UserRole::SuperAdmin), $registration->fresh()];
    }
}
