<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('registration_document_id', 'doc_verif_log_document_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_verification_logs');
    }
};
