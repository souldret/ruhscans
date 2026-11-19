<?php

namespace App\Services\Security;

use Illuminate\Http\Request;

class ClientAddress
{
    public function ip(?Request $request = null): string
    {
        $request ??= request();

        return (string) $request->ip();
    }

    public function key(?Request $request = null): string
    {
        $request ??= request();
        $user = $request->user();

        if ($user) {
            return 'u:'.$user->getAuthIdentifier();
        }

        return 'ip:'.$this->ip($request);
    }
}
