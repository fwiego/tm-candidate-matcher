<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['must', 'nice'])->default('must');
            $table->unsignedTinyInteger('weight')->default(1); // вес для расчёта покрытия
            $table->timestamps();

            $table->unique(['request_id', 'technology_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};