<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\NotDisposableEmail;
use App\Services\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, new NotDisposableEmail],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'company' => ['nullable', 'max:0'],
            'cf-turnstile-response' => [app(Turnstile::class)->enabled() ? 'required' : 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ad alanı zorunludur.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'cf-turnstile-response.required' => 'Doğrulama tamamlanmadı.',
            'password.required' => 'Parola alanı zorunludur.',
            'password.min' => 'Şifre en az 12 karakter, büyük harf, küçük harf ve rakam içermeli.',
            'password.confirmed' => 'Parola tekrarı eşleşmiyor.',
            'company.max' => 'Kayıt tamamlanamadı.',
        ];
    }
}
