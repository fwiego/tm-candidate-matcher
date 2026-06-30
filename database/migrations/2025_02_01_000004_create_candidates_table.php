<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('file_path'); // путь к загруженному резюме (PDF/DOCX)
            $table->longText('raw_text')->nullable(); // распознанный текст из файла
            $table->enum('grade', ['junior', 'middle', 'senior', 'lead'])->nullable();
            $table->string('location')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};