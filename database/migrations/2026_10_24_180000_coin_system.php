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
            $table->bigInteger('coin_balance')->default(0)->after('vip_until');
        });

        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->bigInteger('amount');
            $table->bigInteger('balance_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('coin_code_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('coin_amount');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('coin_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('batch_id')->constrained('coin_code_batches')->restrictOnDelete();
            $table->unsignedInteger('coin_amount');
            $table->string('status')->default('unused');
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('game_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('old_price');
            $table->unsignedInteger('new_price');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('game_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('coins_paid');
            $table->timestamp('unlocked_at')->useCurrent();
            $table->unique(['user_id', 'game_id']);
        });

        Schema::create('coin_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('coin_amount');
            $table->unsignedInteger('bonus_coin')->default(0);
            $table->string('price_label');
            $table->string('url');
            $table->string('button_text')->default('Satın al');
            $table->string('badge')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('coin_package_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['coin_package_id', 'created_at']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_coin_balance_non_negative');
            DB::statement('ALTER TABLE coin_transactions DROP CONSTRAINT IF EXISTS coin_transactions_balance_non_negative');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_package_clicks');
        Schema::dropIfExists('coin_packages');
        Schema::dropIfExists('game_unlocks');
        Schema::dropIfExists('game_price_history');
        Schema::dropIfExists('coin_codes');
        Schema::dropIfExists('coin_code_batches');
        Schema::dropIfExists('coin_transactions');

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['coin_price', 'sale_price', 'sale_ends_at']);
            $table->string('access')->default('free');
            $table->index('access');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('coin_balance');
        });
    }
};
