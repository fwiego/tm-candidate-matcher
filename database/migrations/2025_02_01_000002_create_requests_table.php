<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('position'); // должность
            $table->text('description')->nullable();
            $table->enum('grade', ['junior', 'middle', 'senior', 'lead']);
            $table->string('location')->nullable(); // свободный текст: город/страна
            $table->string('citizenship')->nullable();
            $table->date('needed_by')->nullable(); // дата, к которой нужен кандидат
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};