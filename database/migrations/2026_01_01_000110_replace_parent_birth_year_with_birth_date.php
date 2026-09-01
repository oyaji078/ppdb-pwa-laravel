<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parents get a full date of birth, entered with a native date picker, instead
 * of a bare year typed into a number box.
 *
 * Existing rows only ever knew the year, so they are carried over as 1 January
 * of that year. That is the most the old column can honestly say; the day and
 * month were never collected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_guardians', function (Blueprint $table): void {
            $table->date('birth_date')->nullable()->after('nik');
        });

        DB::table('parent_guardians')
            ->whereNotNull('birth_year')
            ->update(['birth_date' => DB::raw($this->yearToDateExpression())]);

        Schema::table('parent_guardians', function (Blueprint $table): void {
            $table->dropColumn('birth_year');
        });
    }

    public function down(): void
    {
        Schema::table('parent_guardians', function (Blueprint $table): void {
            $table->unsignedSmallInteger('birth_year')->nullable()->after('nik');
        });

        DB::table('parent_guardians')
            ->whereNotNull('birth_date')
            ->update(['birth_year' => DB::raw($this->dateToYearExpression())]);

        Schema::table('parent_guardians', function (Blueprint $table): void {
            $table->dropColumn('birth_date');
        });
    }

    /**
     * The suite runs on SQLite and production on Postgres, with MySQL still
     * supported for a self-hosted install, so the date arithmetic is written
     * for whichever driver is connected.
     *
     * Postgres will not coerce text into a date column on its own, which is
     * why its branch casts explicitly rather than relying on concatenation
     * alone as MySQL does.
     */
    private function yearToDateExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "birth_year || '-01-01'",
            'pgsql' => "CAST(birth_year || '-01-01' AS DATE)",
            default => "CONCAT(birth_year, '-01-01')",
        };
    }

    private function dateToYearExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', birth_date) AS INTEGER)",
            'pgsql' => 'EXTRACT(YEAR FROM birth_date)',
            default => 'YEAR(birth_date)',
        };
    }
};
