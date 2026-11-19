<?php

namespace App\Policies;

use App\Models\Series;
use App\Models\User;

class SeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_CEVIRI]);
    }

    public function view(User $user, Series $series): bool
    {
        return $this->manage($user, $series);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN)
            || ($user->hasRole(User::ROLE_CEVIRI) && $user->translator_group_id !== null);
    }

    public function update(User $user, Series $series): bool
    {
        return $this->manage($user, $series);
    }

    public function delete(User $user, Series $series): bool
    {
        return $this->manage($user, $series);
    }

    private function manage(User $user, Series $series): bool
    {
        if ($user->hasRole(User::ROLE_ADMIN)) {
            return true;
        }

        return $user->hasRole(User::ROLE_CEVIRI)
            && $user->translator_group_id !== null
            && (int) $series->translator_group_id === (int) $user->translator_group_id;
    }
}
