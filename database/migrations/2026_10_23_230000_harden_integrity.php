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
            $table->softDeletes();
        });

        Schema::table('games', function (Blueprint $table) {
            $table->softDeletes();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE games ADD CONSTRAINT games_translation_percent_range CHECK (translation_percent BETWEEN 0 AND 100)');
            DB::statement('ALTER TABLE games ADD CONSTRAINT games_size_non_negative CHECK (size_mb >= 0)');
            DB::statement('ALTER TABLE vip_codes ADD CONSTRAINT vip_codes_duration_positive CHECK (duration_days > 0)');
            DB::statement('ALTER TABLE code_batches ADD CONSTRAINT code_batches_positive CHECK (quantity > 0 AND duration_days > 0)');
            DB::statement('ALTER TABLE game_files ADD CONSTRAINT game_files_size_non_negative CHECK (size_bytes >= 0)');
        }

        Schema::table('downloads', function (Blueprint $table) {
            $table->date('day')->nullable()->after('ip');
        });

        foreach (DB::table('downloads')->whereNull('day')->get(['id', 'created_at']) as $row) {
            DB::table('downloads')->where('id', $row->id)->update([
                'day' => substr((string) $row->created_at, 0, 10),
            ]);
        }

        Schema::table('downloads', function (Blueprint $table) {
            $table->unique(['user_id', 'game_file_id', 'day']);
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE game_files DROP CONSTRAINT game_files_size_non_negative');
            DB::statement('ALTER TABLE code_batches DROP CONSTRAINT code_batches_positive');
            DB::statement('ALTER TABLE vip_codes DROP CONSTRAINT vip_codes_duration_positive');
            DB::statement('ALTER TABLE games DROP CONSTRAINT games_size_non_negative');
            DB::statement('ALTER TABLE games DROP CONSTRAINT games_translation_percent_range');
        }

        Schema::table('downloads', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'game_file_id', 'day']);
            $table->dropColumn('day');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
