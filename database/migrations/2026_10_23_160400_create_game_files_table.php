<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('platform');
            $table->string('storage_path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('version')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_files');
    }
};
