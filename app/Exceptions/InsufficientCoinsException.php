<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientCoinsException extends RuntimeException
{
    public function __construct(
        public readonly int $balance,
        public readonly int $needed,
    ) {
        parent::__construct('Yetersiz bakiye.');
    }
}
