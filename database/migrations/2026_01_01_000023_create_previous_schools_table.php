<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('previous_schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('school_name', 150);
            $table->string('npsn', 20)->nullable();
            $table->string('nsm', 20)->nullable();
            $table->string('school_type', 30)->nullable();
            $table->string('school_status', 30)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('regency', 100)->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->timestamps();

            $table->index('school_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previous_schools');
    }
};
