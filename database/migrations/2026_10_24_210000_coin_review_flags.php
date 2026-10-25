<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('coin_review')->default(false);
        });

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_coin_balance_non_negative');
        DB::statement('ALTER TABLE coin_transactions DROP CONSTRAINT IF EXISTS coin_transactions_balance_non_negative');
        DB::statement('ALTER TABLE users ALTER COLUMN coin_balance TYPE bigint');
        DB::statement('ALTER TABLE coin_transactions ALTER COLUMN balance_after TYPE bigint');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('coin_review');
        });
    }
};
