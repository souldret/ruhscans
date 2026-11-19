<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('ruh_tasi_bakiye')->default(0)->after('password');
            $table->timestamp('vip_bitis')->nullable()->after('ruh_tasi_bakiye');
            $table->boolean('gizli_mod')->default(false)->after('vip_bitis');
            $table->boolean('kapak_blur')->default(true)->after('gizli_mod');
            $table->jsonb('etiket_karaliste')->nullable()->after('kapak_blur');
            $table->text('two_factor_secret')->nullable()->after('etiket_karaliste');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ruh_tasi_bakiye',
                'vip_bitis',
                'gizli_mod',
                'kapak_blur',
                'etiket_karaliste',
                'two_factor_secret',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
