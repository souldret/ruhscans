<?php

namespace App\Console\Commands;

use App\Services\VipCoinMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateVipToCoins extends Command
{
    protected $signature = 'coins:migrate-vip {--dry-run : Değişiklik yapmadan raporu yaz}';

    protected $description = 'Kullanılmamış VIP kodlarını ve süren üyelikleri coin\'e taşır';

    public function handle(VipCoinMigration $migration): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            $backup = $this->backup();
            $this->info('Yedek: '.$backup);
        }

        $plan = $migration->plan();
        $applied = $dryRun ? null : $migration->apply();

        $report = $this->report($migration, $plan, $applied, $dryRun);
        $path = storage_path('app/coin-migration-'.now()->format('Ymd-His').'.json');
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (! is_string($json)) {
            $this->error('Rapor yazılamadı.');

            return self::FAILURE;
        }

        File::put($path, $json);

        $this->line($dryRun ? 'Dry-run. Veritabanına yazılmadı.' : 'Geçiş uygulandı.');
        $this->line(sprintf(
            'Kod: %d · Üye: %d · Oyun: %d',
            count($plan['codes']),
            count($plan['members']),
            count($plan['games']),
        ));
        $this->info('Rapor: '.$path);

        return self::SUCCESS;
    }

    private function backup(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $target = $dir.'/db-'.now()->format('Ymd-His').'.sqlite';

        $database = (string) config('database.connections.sqlite.database');

        if ($database !== '' && $database !== ':memory:' && is_file($database)) {
            File::copy($database, $target);

            return $target;
        }

        File::put($target, '-- bu bağlantı dosya tabanlı değil: '.config('database.default'));

        return $target;
    }

    /**
     * @param  array{codes: list<array<string, mixed>>, members: list<array<string, mixed>>, games: list<array<string, mixed>>}  $plan
     * @param  array{codes: int, members: int, coins: int, games: int}|null  $applied
     * @return array<string, mixed>
     */
    private function report(VipCoinMigration $migration, array $plan, ?array $applied, bool $dryRun): array
    {
        return [
            'dry_run' => $dryRun,
            'rate' => $migration->rate(),
            'default_game_price' => (int) config('coins.default_vip_game_price'),
            'live_members' => $migration->hasLiveMembers(),
            'planned' => [
                'codes' => count($plan['codes']),
                'members' => count($plan['members']),
                'games' => count($plan['games']),
            ],
            'applied' => $applied,
            'codes' => $plan['codes'],
            'members' => $plan['members'],
            'games' => $plan['games'],
        ];
    }
}
