<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => trim((string) $this->input('subject')),
            'body' => trim((string) $this->input('body')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Konuyu yaz.',
            'subject.min' => 'Konu en az 3 karakter olsun.',
            'subject.max' => 'Konu çok uzun.',
            'body.required' => 'Mesajını yaz.',
            'body.min' => 'Mesaj çok kısa.',
            'body.max' => 'Mesaj çok uzun.',
        ];
    }
}
