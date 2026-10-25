<?php

namespace App\Console\Commands;

use App\Services\AdminAlerter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCoinLedger extends Command
{
    protected $signature = 'coins:audit';

    protected $description = 'Her üyenin bakiyesini defter toplamıyla karşılaştırır';

    public function handle(AdminAlerter $alerter): int
    {
        $drift = DB::table('users')
            ->leftJoin('coin_transactions', 'coin_transactions.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.email', 'users.coin_balance')
            ->havingRaw('users.coin_balance != coalesce(sum(coin_transactions.amount), 0)')
            ->orderBy('users.id')
            ->get([
                'users.id',
                'users.email',
                'users.coin_balance',
                DB::raw('coalesce(sum(coin_transactions.amount), 0) as ledger_total'),
            ]);

        if ($drift->isEmpty()) {
            $this->info('Defter bakiyelerle tutuyor.');

            return self::SUCCESS;
        }

        $detail = $drift
            ->map(fn ($row) => sprintf('#%d %s: bakiye %d, defter %d', $row->id, $row->email, $row->coin_balance, $row->ledger_total))
            ->implode("\n");

        $this->error($detail);
        $alerter->send('coin-audit', 'Coin defteri bakiyeyle tutmuyor', $detail);

        return self::FAILURE;
    }
}
