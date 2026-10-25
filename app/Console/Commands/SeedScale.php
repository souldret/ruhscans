<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\CodeBatch;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\Tag;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedScale extends Command
{
    protected $signature = 'db:seed-scale {--database=database/scale.sqlite}';

    protected $description = 'Yerel ölçek testi: 10.000 oyun, 50.000 kullanıcı, 200.000 kod, 500.000 indirme.';

    public function handle(): int
    {
        $relative = (string) $this->option('database');
        $path = base_path($relative);

        if (is_file($path)) {
            unlink($path);
        }

        config(['database.connections.sqlite.database' => $path]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->call('migrate', ['--force' => true]);

        $password = Hash::make('password');
        $now = now()->toDateTimeString();

        $this->users($password, $now);
        $tagIds = $this->tags($now);
        $gameIds = $this->games($now);
        $this->pivot($gameIds, $tagIds);
        $fileIds = $this->files($gameIds, $now);
        $this->codes($now);
        $this->downloads($fileIds, $now);

        $this->info('Hazır: '.$path);

        return self::SUCCESS;
    }

    private function users(string $password, string $now): void
    {
        $this->info('kullanıcı');

        foreach (array_chunk(range(1, 50000), 1000) as $chunk) {
            User::query()->insert(array_map(fn (int $i) => [
                'name' => 'Uye '.$i,
                'email' => 'uye'.$i.'@scale.test',
                'email_verified_at' => $now,
                'password' => $password,
                'role' => UserRole::Member->value,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }

    /**
     * @return list<int>
     */
    private function tags(string $now): array
    {
        foreach (['Gizem', 'Korku', 'Romantik', 'Macera', 'Şiir'] as $name) {
            Tag::query()->insert([
                'name' => $name,
                'slug' => Slug::from($name).'-olcek',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return array_values(Tag::query()->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * @return list<int>
     */
    private function games(string $now): array
    {
        $this->info('oyun');

        foreach (array_chunk(range(1, 10000), 500) as $chunk) {
            Game::query()->insert(array_map(fn (int $i) => [
                'title' => $i % 50 === 0 ? 'Şehir Işığı '.$i : 'Oyun '.$i,
                'slug' => 'olcek-oyun-'.$i,
                'developer' => 'Stüdyo '.($i % 100),
                'description' => 'Ölçek testi.',
                'translation_status' => 'ongoing',
                'translation_percent' => $i % 100,
                'size_mb' => 100,
                'coin_price' => $i % 3 === 0 ? 100 : 0,
                'is_published' => true,
                'published_at' => now()->subMinutes($i)->toDateTimeString(),
                'view_count' => $i,
                'download_count' => $i % 1000,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        return array_values(Game::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * @param  list<int>  $gameIds
     * @param  list<int>  $tagIds
     */
    private function pivot(array $gameIds, array $tagIds): void
    {
        $rows = [];

        foreach ($gameIds as $index => $gameId) {
            $rows[] = ['game_id' => $gameId, 'tag_id' => $tagIds[$index % count($tagIds)]];

            if (count($rows) === 1000) {
                DB::table('game_tag')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('game_tag')->insert($rows);
        }
    }

    /**
     * @param  list<int>  $gameIds
     * @return list<int>
     */
    private function files(array $gameIds, string $now): array
    {
        foreach (array_chunk($gameIds, 1000) as $chunk) {
            GameFile::query()->insert(array_map(fn (int $id) => [
                'game_id' => $id,
                'label' => 'Windows',
                'platform' => 'windows',
                'storage_path' => 'oyunlar/olcek-'.$id.'/windows.zip',
                'size_bytes' => 1024,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        return array_values(GameFile::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    private function codes(string $now): void
    {
        $this->info('kod');
        $batch = CodeBatch::query()->create([
            'name' => 'Ölçek',
            'quantity' => 200000,
            'duration_days' => 30,
            'created_by' => User::query()->value('id'),
        ]);

        $rows = [];

        for ($i = 1; $i <= 200000; $i++) {
            $rows[] = [
                'code' => sprintf('SC-%04d-%04d-%04d', intdiv($i, 10000), intdiv($i, 100) % 100, $i % 100),
                'batch_id' => $batch->id,
                'duration_days' => 30,
                'status' => 'unused',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === 1000) {
                DB::table('vip_codes')->insert($rows);
                $rows = [];
            }
        }
    }

    /**
     * @param  list<int>  $fileIds
     */
    private function downloads(array $fileIds, string $now): void
    {
        $this->info('indirme');
        $fileCount = count($fileIds);
        $rows = [];

        for ($i = 1; $i <= 500000; $i++) {
            $rows[] = [
                'user_id' => (($i - 1) % 50000) + 1,
                'game_file_id' => $fileIds[intdiv($i - 1, 50000) % $fileCount],
                'ip' => '127.0.0.1',
                'day' => now()->subDays(intdiv($i - 1, 500000))->toDateString(),
                'created_at' => now()->subSeconds($i % 86400)->toDateTimeString(),
            ];

            if (count($rows) === 1000) {
                DB::table('downloads')->insert($rows);
                $rows = [];
            }
        }
    }
}
