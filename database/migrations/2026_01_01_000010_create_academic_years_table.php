<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->boolean('is_active')->default(false);
            $table->boolean('registration_open')->default(false);
            $table->timestamps();

            $table->index('is_active');
            $table->index('start_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
