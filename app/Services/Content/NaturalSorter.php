<?php

namespace App\Services\Content;

class NaturalSorter
{
    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    public function sort(array $paths): array
    {
        usort($paths, function (string $left, string $right): int {
            return strnatcasecmp(basename($left), basename($right));
        });

        return array_values($paths);
    }
}
