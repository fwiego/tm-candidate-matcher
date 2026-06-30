<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('group')->nullable(); // e.g. "Backend", "Frontend", "DevOps"
            $table->json('synonyms')->nullable(); // e.g. ["JS", "ECMAScript"] for "JavaScript"
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};