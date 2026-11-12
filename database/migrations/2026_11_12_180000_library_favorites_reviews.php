<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_unlocks', function (Blueprint $table) {
            $table->string('seen_game_version', 50)->nullable()->after('coins_paid');
            $table->string('seen_translation_version', 50)->nullable()->after('seen_game_version');
        });

        Schema::create('game_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });

        Schema::table('game_reviews', function (Blueprint $table) {
            $table->boolean('spoiler')->default(false)->after('body');
        });

        Schema::create('game_review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['game_review_id', 'user_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE games ADD FULLTEXT games_search_text_fulltext (search_text)');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE games DROP INDEX games_search_text_fulltext');
        }

        Schema::dropIfExists('game_review_votes');

        Schema::table('game_reviews', function (Blueprint $table) {
            $table->dropColumn('spoiler');
        });

        Schema::dropIfExists('game_favorites');

        Schema::table('game_unlocks', function (Blueprint $table) {
            $table->dropColumn(['seen_game_version', 'seen_translation_version']);
        });
    }
};
