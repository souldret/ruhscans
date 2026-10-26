<?php

namespace App\Filament\Pages\Auth;

use App\Services\Turnstile;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            Placeholder::make('turnstile')
                ->hiddenLabel()
                ->content(fn (): HtmlString => new HtmlString(
                    '<div class="cf-turnstile" data-sitekey="'.e(app(Turnstile::class)->siteKey()).'"></div>'
                    .'<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
                ))
                ->visible(fn (): bool => app(Turnstile::class)->enabled()),
            $this->getRememberFormComponent(),
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $token = request()->input('cf-turnstile-response');

        if (! app(Turnstile::class)->passes(is_string($token) ? $token : null, request()->ip())) {
            throw ValidationException::withMessages([
                'data.email' => 'Doğrulama tamamlanmadı.',
            ]);
        }

        return parent::authenticate();
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
