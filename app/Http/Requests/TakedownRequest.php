<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TakedownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'reason' => ['required', 'string', 'max:2000'],
            'company' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Adını yaz.',
            'email.required' => 'E-posta gerekli.',
            'email.email' => 'E-posta geçersiz.',
            'url.required' => 'İçerik adresini yaz.',
            'url.url' => 'Adres geçersiz.',
            'reason.required' => 'Gerekçeyi yaz.',
        ];
    }
}
