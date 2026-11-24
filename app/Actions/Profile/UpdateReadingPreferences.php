<?php

namespace App\Actions\Profile;

use App\Models\Tag;
use App\Models\User;

class UpdateReadingPreferences
{
    /**
     * @param  array{kapak_blur: bool, gizli_mod: bool, notr_sekme_baslik?: bool, panik_url: string, etiket_karaliste: list<string>}  $data
     */
    public function handle(User $user, array $data): User
    {
        $allowed = Tag::query()->whereIn('slug', $data['etiket_karaliste'])->pluck('slug')->all();

        $user->forceFill([
            'kapak_blur' => $data['kapak_blur'],
            'gizli_mod' => $data['gizli_mod'],
            'notr_sekme_baslik' => (bool) ($data['notr_sekme_baslik'] ?? $user->notr_sekme_baslik),
            'panik_url' => $data['panik_url'],
            'etiket_karaliste' => array_values($allowed),
        ])->save();

        return $user->refresh();
    }
}
