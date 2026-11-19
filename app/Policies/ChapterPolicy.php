<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\User;

class ChapterPolicy
{
    public function __construct(private readonly SeriesPolicy $seriesPolicy) {}

    public function viewAny(User $user): bool
    {
        return $this->seriesPolicy->viewAny($user);
    }

    public function view(User $user, Chapter $chapter): bool
    {
        return $this->seriesPolicy->view($user, $chapter->series);
    }

    public function create(User $user): bool
    {
        return $this->seriesPolicy->create($user);
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $this->seriesPolicy->update($user, $chapter->series);
    }

    public function delete(User $user, Chapter $chapter): bool
    {
        return $this->seriesPolicy->delete($user, $chapter->series);
    }
}
