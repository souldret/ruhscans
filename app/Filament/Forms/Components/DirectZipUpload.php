<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\ViewField;
use Illuminate\Database\Eloquent\Model;

class DirectZipUpload extends ViewField
{
    protected string $view = 'filament.direct-zip-upload';

    /** @var callable(?Model): int */
    protected $chapterId;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function chapterId(callable $callback): static
    {
        $this->chapterId = $callback;

        return $this;
    }

    public function getChapterId(): int
    {
        return (int) ($this->chapterId)($this->getRecord());
    }
}
