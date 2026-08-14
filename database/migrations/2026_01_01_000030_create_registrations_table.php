<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();

            // Assigned only at final submit; NULL while the wizard is still a draft.
            $table->char('registration_number', 10)->nullable();

            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_wave_id')->constrained()->restrictOnDelete();
            $table->foreignId('admission_track_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();

            $table->string('access_code_hash')->nullable();

            $table->string('registration_status', 30)->default('draft');
            $table->string('selection_status', 30)->default('pending');
            $table->string('reregistration_status', 30)->default('not_required');

            $table->string('current_step', 30)->default('pendaftaran');
            $table->boolean('statement_agreed')->default(false);

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('selected_at')->nullable();
            $table->timestamp('reregistered_at')->nullable();

            // Bumped on access-code reset so previously issued sessions stop working.
            $table->unsignedInteger('session_version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('registration_number');
            $table->unique(['applicant_id', 'academic_year_id']);

            $table->index('academic_year_id');
            $table->index('registration_wave_id');
            $table->index('admission_track_id');
            $table->index('program_id');
            $table->index('registration_status');
            $table->index('selection_status');
            $table->index('reregistration_status');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
