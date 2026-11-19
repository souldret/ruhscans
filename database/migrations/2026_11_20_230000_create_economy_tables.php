<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('yon', 8);
            $table->unsignedBigInteger('miktar');
            $table->unsignedBigInteger('bakiye_sonrasi');
            $table->string('sebep', 64);
            $table->string('referans_tip')->nullable();
            $table->unsignedBigInteger('referans_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['referans_tip', 'referans_id']);
        });

        Schema::create('code_batches', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('tur', 16);
            $table->unsignedInteger('deger');
            $table->timestamp('son_kullanma')->nullable();
            $table->unsignedInteger('adet');
            $table->foreignId('olusturan_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('redeem_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('code_batches')->cascadeOnDelete();
            $table->string('tur', 16);
            $table->unsignedInteger('deger');
            $table->timestamp('son_kullanma')->nullable();
            $table->string('durum', 16)->default('aktif');
            $table->string('kod_hash', 64)->unique();
            $table->string('son_dort', 4);
            $table->foreignId('kullanan_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kullanim_tarihi')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'durum']);
            $table->index('son_dort');
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_ruh_tasi_bakiye_nonneg CHECK (ruh_tasi_bakiye >= 0)');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_ruh_tasi_bakiye_nonneg');
        }

        Schema::dropIfExists('redeem_codes');
        Schema::dropIfExists('code_batches');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('site_settings');
    }
};
