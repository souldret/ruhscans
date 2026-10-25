<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Defter satırının işaret ettiği kayıt. type model sınıfı, id birincil anahtar.
 */
final class ModelRef
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {}

    public static function of(Model $model): self
    {
        return new self($model::class, (int) $model->getKey());
    }
}
