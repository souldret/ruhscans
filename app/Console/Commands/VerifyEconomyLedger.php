<?php

namespace App\Console\Commands;

use App\Enums\LedgerDirection;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyEconomyLedger extends Command
{
    protected $signature = 'economy:verify';

    protected $description = 'Kullanıcı ruh taşı bakiyelerinin wallet_transactions defteri ile tutarlılığını doğrular.';

    public function handle(): int
    {
        $ledgerTotals = DB::table('wallet_transactions')
            ->whereNotNull('user_id')
            ->selectRaw(
                "user_id, SUM(CASE WHEN yon = ? THEN miktar ELSE -miktar END) as net_total",
                [LedgerDirection::Kredi->value]
            )
            ->groupBy('user_id')
            ->pluck('net_total', 'user_id');

        $mismatches = [];

        User::query()
            ->select(['id', 'ruh_tasi_bakiye'])
            ->chunkById(500, function ($users) use ($ledgerTotals, &$mismatches): void {
                foreach ($users as $user) {
                    $expected = (int) ($ledgerTotals[$user->id] ?? 0);
                    $actual = (int) $user->ruh_tasi_bakiye;

                    if ($actual < 0 || $actual !== $expected) {
                        $mismatches[] = [
                            'user_id' => $user->id,
                            'actual_balance' => $actual,
                            'ledger_total' => $expected,
                            'diff' => $actual - $expected,
                        ];
                    }
                }
            });

        if ($mismatches !== []) {
            Log::critical('Ekonomi ledger tutarsızlığı tespit edildi!', [
                'mismatch_count' => count($mismatches),
                'sample' => array_slice($mismatches, 0, 20),
            ]);

            activity('economy-audit')
                ->withProperties([
                    'mismatch_count' => count($mismatches),
                    'sample' => array_slice($mismatches, 0, 20),
                ])
                ->log('UYARI: Kullanıcı ruh taşı bakiyesi ile ledger toplamı uyuşmuyor!');

            $this->error(count($mismatches).' kullanıcıda bakiye/ledger tutarsızlığı bulundu.');

            return self::FAILURE;
        }

        $this->info('Ekonomi defteri tutarlı: tüm kullanıcı bakiyeleri ledger toplamıyla eşleşiyor.');

        return self::SUCCESS;
    }
}
