<?php

namespace App\Models;

use App\Enums\SupportThreadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportThread extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject',
        'status',
        'last_message_at',
        'unread_by_user',
        'unread_by_admin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SupportThreadStatus::class,
            'last_message_at' => 'datetime',
            'unread_by_user' => 'boolean',
            'unread_by_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SupportMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id');
    }

    /**
     * @return HasOne<SupportMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    public function post(User $author, string $body): SupportMessage
    {
        $staff = $author->isAdmin() && ! $author->is($this->user);

        $message = $this->messages()->create([
            'user_id' => $author->id,
            'body' => $body,
            'is_staff' => $staff,
        ]);

        $this->forceFill([
            'status' => $staff ? SupportThreadStatus::Answered : SupportThreadStatus::Open,
            'last_message_at' => $message->created_at ?? now(),
            'unread_by_admin' => ! $staff,
            'unread_by_user' => $staff,
        ])->save();

        return $message;
    }

    public function markReadFor(User $user): void
    {
        $dirty = [];

        if ($user->is($this->user) && $this->unread_by_user) {
            $dirty['unread_by_user'] = false;
        }

        if ($user->isAdmin() && $this->unread_by_admin) {
            $dirty['unread_by_admin'] = false;
        }

        if ($dirty !== []) {
            $this->forceFill($dirty)->save();
        }
    }

    public function close(): void
    {
        $this->forceFill([
            'status' => SupportThreadStatus::Closed,
            'unread_by_user' => false,
            'unread_by_admin' => false,
        ])->save();
    }
}
