<?php

namespace App\Enums;

enum CoinTransactionType: string
{
    case CodeRedeem = 'code_redeem';
    case GameUnlock = 'game_unlock';
    case Refund = 'refund';
    case AdminAdjust = 'admin_adjust';
    case Bonus = 'bonus';
    case Migration = 'migration';
    case Clawback = 'clawback';

    public function label(): string
    {
        return match ($this) {
            self::CodeRedeem => 'Kod kullanımı',
            self::GameUnlock => 'Oyun kilidi',
            self::Refund => 'İade',
            self::AdminAdjust => 'Yönetici düzeltmesi',
            self::Bonus => 'Bonus',
            self::Migration => 'Geçiş',
            self::Clawback => 'Satış iadesi',
        };
    }
}
