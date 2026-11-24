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
            $table->string('avatar_path')->nullable()->after('email');
            $table->boolean('notr_sekme_baslik')->default(false)->after('gizli_mod');
        });

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity')->index();
            $table->timestamps();

            $table->index(['user_id', 'last_activity']);
        });

        // Hesap silindiğinde mali denetim bütünlüğü için ledger kayıtları anonimleştirilerek saklanır.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE wallet_transactions DROP CONSTRAINT IF EXISTS wallet_transactions_user_id_foreign');
            DB::statement('ALTER TABLE wallet_transactions ALTER COLUMN user_id DROP NOT NULL');
            DB::statement('ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        } else {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'notr_sekme_baslik']);
        });
    }
};
