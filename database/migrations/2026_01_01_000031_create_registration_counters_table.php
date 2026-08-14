<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One counter row per (academic year, wave). The row is locked with
     * lockForUpdate() during submission so concurrent submits cannot hand out
     * the same sequence number.
     */
    public function up(): void
    {
        Schema::create('registration_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_wave_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['academic_year_id', 'registration_wave_id'], 'reg_counter_year_wave_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_counters');
    }
};
