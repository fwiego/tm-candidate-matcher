<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_requirement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_matched')->default(false); // закрыто ли требование навыком кандидата
            $table->timestamps();

            $table->unique(['assessment_id', 'requirement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_requirement');
    }
};