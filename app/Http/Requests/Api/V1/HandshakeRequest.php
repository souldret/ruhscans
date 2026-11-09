<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class HandshakeRequest extends FormRequest
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
            'game_slug' => ['required', 'string', 'max:160'],
            'game_version' => ['required', 'string', 'max:32'],
            'launcher_version' => ['required', 'string', 'max:32'],
            'device_id' => ['required', 'string', 'max:128'],
            'client_nonce' => ['required', 'string', 'min:16', 'max:128'],
        ];
    }
}
