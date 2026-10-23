<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('password');
            $table->timestamp('vip_until')->nullable()->after('role');
            $table->string('avatar_path')->nullable()->after('vip_until');

            $table->index('role');
            $table->index('vip_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['vip_until']);
            $table->dropColumn(['role', 'vip_until', 'avatar_path']);
        });
    }
};
