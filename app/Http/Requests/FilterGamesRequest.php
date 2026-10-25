<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterGamesRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:100'],
            'etiket' => ['nullable', 'array', 'max:8'],
            'etiket.*' => ['string', 'max:80'],
            'erisim' => ['nullable', Rule::in(['ucretsiz', 'ucretli', 'aldiklarim'])],
            'ceviri' => ['nullable', Rule::in(['ongoing', 'complete'])],
            'sirala' => ['nullable', Rule::in(['yeni', 'populer', 'guncellenen', 'fiyat-artan', 'fiyat-azalan'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.max' => 'Arama en fazla 100 karakter olabilir.',
            'erisim.in' => 'Erişim filtresi geçersiz.',
            'ceviri.in' => 'Çeviri filtresi geçersiz.',
            'sirala.in' => 'Sıralama seçeneği geçersiz.',
        ];
    }
}
