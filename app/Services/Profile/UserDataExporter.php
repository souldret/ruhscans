<?php

namespace App\Services\Profile;

use App\Models\User;

class UserDataExporter
{
    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $library = $user->libraryEntries()
            ->with(['series:id,title,slug', 'lastChapter:id,number'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($entry) => [
                'seri' => $entry->series?->title,
                'slug' => $entry->series?->slug,
                'puan' => $entry->rating,
                'son_bolum' => $entry->lastChapter?->number,
                'son_sayfa' => $entry->last_page_order,
                'tamamlandi' => (bool) $entry->completed,
                'guncellendi' => $entry->updated_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $unlocks = $user->chapterUnlocks()
            ->with(['chapter.series:id,title,slug'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($unlock) => [
                'seri' => $unlock->chapter?->series?->title,
                'bolum' => $unlock->chapter?->number,
                'acilis_tarihi' => $unlock->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $transactions = $user->walletTransactions()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($tx) => [
                'yon' => $tx->yon->value,
                'miktar' => $tx->miktar,
                'bakiye_sonrasi' => $tx->bakiye_sonrasi,
                'sebep' => $tx->sebep,
                'tarih' => $tx->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->log('Kişisel veri dışa aktarıldı (JSON).');

        return [
            'disa_aktarma_tarihi' => now()->toIso8601String(),
            'hesap' => [
                'id' => $user->id,
                'ad' => $user->name,
                'eposta' => $user->email,
                'eposta_dogrulandi' => $user->email_verified_at?->toIso8601String(),
                'uyelik_tarihi' => $user->created_at?->toIso8601String(),
                'ruh_tasi_bakiye' => $user->ruh_tasi_bakiye,
                'vip_bitis' => $user->vip_bitis?->toIso8601String(),
            ],
            'tercihler' => [
                'kapak_blur' => (bool) $user->kapak_blur,
                'gizli_mod' => (bool) $user->gizli_mod,
                'notr_sekme_baslik' => (bool) $user->notr_sekme_baslik,
                'panik_url' => $user->panik_url,
                'okuma_modu' => $user->okuma_modu,
                'etiket_karaliste' => array_values($user->etiket_karaliste ?? []),
            ],
            'kutuphane_ve_gecmis' => $library,
            'acilan_bolumler' => $unlocks,
            'cuzdan_hareketleri' => $transactions,
        ];
    }
}
