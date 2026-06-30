<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('coverage_percent'); // 0-100
            $table->boolean('grade_match')->default(false);
            $table->boolean('location_match')->default(false);
            $table->boolean('citizenship_match')->default(false);
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['request_id', 'candidate_id']);
            $table->index('coverage_percent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};