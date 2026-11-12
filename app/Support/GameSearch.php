<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class GameSearch
{
    /**
     * @param  Builder<\App\Models\Game>  $query
     */
    public static function apply(Builder $query, string $term): void
    {
        $folded = Slug::fold($term);

        if ($folded === '') {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $query->whereFullText('search_text', $folded);

            return;
        }

        $query->where('search_text', 'like', '%'.$folded.'%');
    }
}
