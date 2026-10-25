<?php

namespace App\Http\Requests;

use App\Models\SupportThread;
use Illuminate\Foundation\Http\FormRequest;

class ReplySupportThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');
        $user = $this->user();

        return $thread instanceof SupportThread
            && $user !== null
            && $user->can('reply', $thread);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => trim((string) $this->input('body')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Mesajını yaz.',
            'body.min' => 'Mesaj çok kısa.',
            'body.max' => 'Mesaj çok uzun.',
        ];
    }
}
