<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();

            $table->string('original_name', 255);
            $table->string('stored_name', 120);
            $table->string('storage_path', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 10);
            $table->unsignedBigInteger('file_size');

            $table->string('verification_status', 30)->default('pending');
            $table->text('verification_note')->nullable();

            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['registration_id', 'document_type_id'], 'registration_document_unique');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_documents');
    }
};
