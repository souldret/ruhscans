<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('read_blocked_until');
        });

        Schema::table('code_batches', function (Blueprint $table) {
            $table->text('not')->nullable()->after('ad');
        });

        Schema::create('code_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('code_batches')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('path');
            $table->timestamp('expires_at');
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_exports');

        Schema::table('code_batches', function (Blueprint $table) {
            $table->dropColumn('not');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
        });
    }
};
