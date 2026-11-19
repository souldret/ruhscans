<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translator_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('translator_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translator_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('alternative_names')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('type');
            $table->string('status');
            $table->string('content_level');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('author')->nullable();
            $table->string('artist')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'content_level']);
            $table->index('hidden');
            $table->index('year');
        });

        Schema::create('series_tag', function (Blueprint $table) {
            $table->foreignId('series_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['series_id', 'tag_id']);
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->cascadeOnDelete();
            $table->decimal('number', 8, 2);
            $table->string('title')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('ruh_tasi_fiyat')->default(0);
            $table->boolean('vip_ucretsiz')->default(false);
            $table->timestamp('erken_erisim_bitis')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['series_id', 'number']);
            $table->index('published_at');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->string('r2_path');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->unique(['chapter_id', 'order']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reportable');
            $table->text('reason');
            $table->string('status')->default('bekliyor');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('chapter_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('bekliyor');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->json('failures')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement("ALTER TABLE series ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(alternative_names::text, ''))) STORED");
            DB::statement('CREATE INDEX series_search_vector_idx ON series USING GIN (search_vector)');
            DB::statement('CREATE INDEX series_title_trgm_idx ON series USING GIN (title gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_uploads');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('series_tag');
        Schema::dropIfExists('series');
        Schema::dropIfExists('tags');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('translator_group_id');
        });

        Schema::dropIfExists('translator_groups');
    }
};
