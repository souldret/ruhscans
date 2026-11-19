<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CEVIRI = 'ceviri_ekibi';

    public const ROLE_UYE = 'uye';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ruh_tasi_bakiye',
        'vip_bitis',
        'gizli_mod',
        'kapak_blur',
        'etiket_karaliste',
        'panik_url',
        'okuma_modu',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'translator_group_id',
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
            'vip_bitis' => 'datetime',
            'gizli_mod' => 'boolean',
            'kapak_blur' => 'boolean',
            'etiket_karaliste' => 'array',
            'panik_url' => 'string',
            'okuma_modu' => 'string',
            'ruh_tasi_bakiye' => 'integer',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function translatorGroup(): BelongsTo
    {
        return $this->belongsTo(TranslatorGroup::class);
    }

    public function libraryEntries(): HasMany
    {
        return $this->hasMany(LibraryEntry::class);
    }

    public function isVip(): bool
    {
        return $this->vip_bitis !== null && $this->vip_bitis->isFuture();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        if (! $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_CEVIRI])) {
            return false;
        }

        if ($this->hasRole(self::ROLE_ADMIN) && ! $this->hasConfirmedTwoFactor()) {
            return false;
        }

        return true;
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'gizli_mod', 'kapak_blur'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
