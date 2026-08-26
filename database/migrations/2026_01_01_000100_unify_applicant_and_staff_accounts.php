<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Everyone signs in through one form, so applicants become `users` too and the
 * role decides where they land.
 *
 * Applicants have no username (they sign in with their e-mail), and their
 * password now lives in `users.password` instead of `registrations.access_code_hash`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Applicants sign in by e-mail and never get a username.
            $table->string('username', 60)->nullable()->change();
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('applicant_id')
                ->constrained()->nullOnDelete();
        });

        $this->backfillApplicantAccounts();

        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropColumn('access_code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('access_code_hash')->nullable()->after('program_id');
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 60)->nullable(false)->change();
        });
    }

    /**
     * Give every existing registration a user account built from its applicant,
     * so nobody is locked out by the switch. The old access code cannot be
     * carried over — it was hashed for a different column — so each account gets
     * a fresh random password that the committee resets on request.
     */
    private function backfillApplicantAccounts(): void
    {
        $rows = DB::table('registrations')
            ->join('applicants', 'applicants.id', '=', 'registrations.applicant_id')
            ->whereNull('registrations.user_id')
            ->select(
                'registrations.id as registration_id',
                'applicants.full_name',
                'applicants.email',
                'applicants.phone',
            )
            ->get();

        foreach ($rows as $row) {
            $email = filled($row->email)
                ? $row->email
                : 'pendaftar'.$row->registration_id.'@tanpa-email.local';

            // An e-mail already taken by a staff account (or a sibling sharing
            // one) must not collide with the unique index.
            if (DB::table('users')->where('email', $email)->exists()) {
                $email = 'pendaftar'.$row->registration_id.'.'.Str::random(6).'@tanpa-email.local';
            }

            $userId = DB::table('users')->insertGetId([
                'username' => null,
                'name' => $row->full_name ?: 'Calon Peserta Didik',
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'role' => UserRole::Applicant->value,
                'phone' => $row->phone,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('registrations')
                ->where('id', $row->registration_id)
                ->update(['user_id' => $userId]);
        }
    }
};
