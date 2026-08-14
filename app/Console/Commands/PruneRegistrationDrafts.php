<?php

namespace App\Console\Commands;

use App\Enums\RegistrationStatus;
use App\Models\Applicant;
use App\Models\Registration;
use App\Services\DocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes abandoned wizard drafts.
 *
 * A visitor who starts the form and never submits leaves behind a draft
 * registration, an empty applicant record and possibly uploaded files. Those
 * also hold on to a NISN, which would otherwise block the same student from
 * registering later.
 */
class PruneRegistrationDrafts extends Command
{
    protected $signature = 'ppdb:prune-drafts
                            {--days=30 : Usia minimal draft dalam hari}
                            {--dry-run : Tampilkan yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus draft pendaftaran yang tidak pernah dikirim beserta berkasnya';

    public function handle(DocumentService $documents): int
    {
        $days = max((int) $this->option('days'), 1);
        $cutoff = now()->subDays($days);

        $drafts = Registration::query()
            ->with(['applicant', 'documents'])
            ->where('registration_status', RegistrationStatus::Draft)
            ->whereNull('submitted_at')
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($drafts->isEmpty()) {
            $this->info(sprintf('Tidak ada draft yang lebih lama dari %d hari.', $days));

            return self::SUCCESS;
        }

        $this->line(sprintf('Ditemukan %d draft yang tidak aktif sejak %s.', $drafts->count(), $cutoff->translatedFormat('d F Y')));

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Nama', 'NISN', 'Terakhir Diubah', 'Berkas'],
                $drafts->map(fn (Registration $draft) => [
                    $draft->id,
                    $draft->applicant->full_name ?: '(kosong)',
                    $draft->applicant->nisn ?: '-',
                    $draft->updated_at->translatedFormat('d M Y'),
                    $draft->documents->count(),
                ])->all()
            );

            $this->comment('Mode dry-run: tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($drafts as $draft) {
            // Files first: a deleted row would leave them orphaned on disk.
            foreach ($draft->documents as $document) {
                $documents->delete($document);
            }

            DB::transaction(function () use ($draft, &$deleted): void {
                $applicantId = $draft->applicant_id;

                $draft->forceDelete();

                // The applicant record exists only to back this draft; remove
                // it once it has no registrations left.
                $applicant = Applicant::query()->withTrashed()->find($applicantId);

                if ($applicant !== null && $applicant->registrations()->withTrashed()->doesntExist()) {
                    $applicant->forceDelete();
                }

                $deleted++;
            });
        }

        $this->info(sprintf('%d draft pendaftaran dihapus.', $deleted));

        return self::SUCCESS;
    }
}
