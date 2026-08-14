<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 20);
            $table->string('name', 150)->nullable();
            $table->string('nik', 20)->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('education', 50)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('monthly_income', 60)->nullable();
            $table->string('phone', 25)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_alive')->default(true);
            $table->timestamps();

            $table->unique(['applicant_id', 'relationship']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_guardians');
    }
};
