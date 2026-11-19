<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('series', function (Blueprint $table) {
            $table->index(['hidden', 'updated_at']);
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->index(['series_id', 'published_at']);
        });

        Schema::table('library_entries', function (Blueprint $table) {
            $table->index('last_read_at');
        });

        Schema::table('redeem_codes', function (Blueprint $table) {
            $table->index('kullanim_tarihi');
        });
    }

    public function down(): void
    {
        Schema::table('redeem_codes', function (Blueprint $table) {
            $table->dropIndex(['kullanim_tarihi']);
        });

        Schema::table('library_entries', function (Blueprint $table) {
            $table->dropIndex(['last_read_at']);
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex(['series_id', 'published_at']);
        });

        Schema::table('series', function (Blueprint $table) {
            $table->dropIndex(['hidden', 'updated_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
