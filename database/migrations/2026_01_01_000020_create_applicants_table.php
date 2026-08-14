<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 20)->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('family_card_number', 20)->nullable();
            $table->string('full_name', 150);
            $table->char('gender', 1)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 30)->nullable();
            $table->unsignedTinyInteger('child_order')->nullable();
            $table->unsignedTinyInteger('siblings_count')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Not unique: the same person may register again in a later
            // academic year, which creates a fresh applicant record.
            // Per-year uniqueness is enforced when a registration is submitted.
            $table->index('nisn');
            $table->index('nik');
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
