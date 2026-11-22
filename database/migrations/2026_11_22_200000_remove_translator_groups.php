<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'translator_group_id')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('translator_group_id'));
        }

        if (Schema::hasColumn('series', 'translator_group_id')) {
            Schema::table('series', fn (Blueprint $table) => $table->dropConstrainedForeignId('translator_group_id'));
        }

        Schema::dropIfExists('translator_groups');
    }

    public function down(): void
    {
        if (! Schema::hasTable('translator_groups')) {
            Schema::create('translator_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('users', 'translator_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('translator_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('series', 'translator_group_id')) {
            Schema::table('series', function (Blueprint $table) {
                $table->foreignId('translator_group_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }
};
