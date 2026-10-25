<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->date('released_on')->nullable()->after('developer');
            $table->unsignedSmallInteger('playtime_hours')->nullable()->after('size_mb');
            $table->unsignedInteger('apk_size_mb')->nullable()->after('playtime_hours');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['released_on', 'playtime_hours', 'apk_size_mb']);
        });
    }
};
