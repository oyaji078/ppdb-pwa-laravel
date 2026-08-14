<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_track_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_track_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['admission_track_id', 'document_type_id'], 'track_document_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_track_document_requirements');
    }
};
