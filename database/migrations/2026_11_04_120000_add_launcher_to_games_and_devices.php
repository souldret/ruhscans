<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('launcher_enabled')->default(false)->after('is_published');
            $table->string('launcher_min_version', 32)->nullable()->after('launcher_enabled');
            $table->text('launcher_message')->nullable()->after('launcher_min_version');
        });

        Schema::create('launcher_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 128);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->string('ip_hash', 64)->nullable();
            $table->string('last_launcher_version', 32)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'game_id', 'device_id']);
            $table->index(['user_id', 'game_id', 'last_seen_at']);
        });

        Schema::create('launcher_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id', 128)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('result', 32);
            $table->string('reason', 64)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'result']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launcher_login_logs');
        Schema::dropIfExists('launcher_devices');

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['launcher_enabled', 'launcher_min_version', 'launcher_message']);
        });
    }
};
