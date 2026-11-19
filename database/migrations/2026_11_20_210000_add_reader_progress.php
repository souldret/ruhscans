<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('okuma_modu')->default('webtoon')->after('panik_url');
        });

        Schema::table('library_entries', function (Blueprint $table) {
            $table->unsignedInteger('last_page_order')->nullable()->after('last_chapter_id');
            $table->boolean('completed')->default(false)->after('last_page_order');
        });

        Schema::create('chapter_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'chapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_unlocks');

        Schema::table('library_entries', function (Blueprint $table) {
            $table->dropColumn(['last_page_order', 'completed']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('okuma_modu');
        });
    }
};
