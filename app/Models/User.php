<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
        'two_factor_secret',
        'two_factor_confirmed_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'vip_until' => 'datetime',
            'coin_balance' => 'integer',
            'coin_review' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasMany<CoinTransaction, $this>
     */
    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    /**
     * @return HasMany<GameUnlock, $this>
     */
    public function unlocks(): HasMany
    {
        return $this->hasMany(GameUnlock::class);
    }

    /**
     * @return HasMany<Download, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    /**
     * @return HasMany<CodeAttempt, $this>
     */
    public function codeAttempts(): HasMany
    {
        return $this->hasMany(CodeAttempt::class);
    }

    /**
     * @return HasMany<CoinCode, $this>
     */
    public function redeemedCodes(): HasMany
    {
        return $this->hasMany(CoinCode::class, 'used_by');
    }

    /**
     * @return HasMany<SupportThread, $this>
     */
    public function supportThreads(): HasMany
    {
        return $this->hasMany(SupportThread::class);
    }
}
