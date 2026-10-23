<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('original_title')->nullable();
            $table->string('developer')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->json('screenshots')->nullable();
            $table->string('game_version')->nullable();
            $table->string('translation_version')->nullable();
            $table->string('translation_status')->default('ongoing');
            $table->unsignedTinyInteger('translation_percent')->default(0);
            $table->unsignedInteger('size_mb')->default(0);
            $table->string('access')->default('free');
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamps();

            $table->index('access');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
