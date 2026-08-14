<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('document_number', 60)->nullable();
            $table->string('storage_path', 255);
            $table->string('checksum', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
