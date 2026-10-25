<?php

use App\Support\Slug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('search_text')->nullable()->index();
            $table->index(['is_published', 'published_at']);
            $table->index(['is_published', 'coin_price', 'published_at']);
            $table->index(['is_published', 'download_count']);
        });

        Schema::table('downloads', function (Blueprint $table) {
            $table->index(['user_id', 'game_file_id', 'day']);
        });

        if (Schema::hasTable('games')) {
            DB::table('games')->orderBy('id')->each(function ($game): void {
                DB::table('games')->where('id', $game->id)->update([
                    'search_text' => Slug::fold(($game->title ?? '').' '.($game->developer ?? '')),
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'published_at']);
            $table->dropIndex(['is_published', 'coin_price', 'published_at']);
            $table->dropIndex(['is_published', 'download_count']);
            $table->dropColumn('search_text');
        });

        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'game_file_id', 'day']);
        });
    }
};
