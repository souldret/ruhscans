<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'positive' => ['required', 'boolean'],
            'body' => ['required', 'string', 'min:8', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'positive.required' => 'Olumlu ya da olumsuz seç.',
 'positive.boolean' => 'Olumlu ya da olumsuz seç.',
            'body.required' => 'Yorumunu yaz.',
            'body.min' => 'Yorum en az 8 karakter olsun.',
            'body.max' => 'Yorum en fazla 1000 karakter olabilir.',
        ];
    }
}
