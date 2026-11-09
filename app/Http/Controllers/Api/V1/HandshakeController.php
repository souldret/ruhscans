<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HandshakeRequest;
use App\Models\User;
use App\Services\Launcher\LauncherHandshake;
use Illuminate\Http\JsonResponse;

class HandshakeController extends Controller
{
    public function __invoke(HandshakeRequest $request, LauncherHandshake $handshake): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $signed = $handshake->issue($user, [
            'game_slug' => (string) $request->validated('game_slug'),
            'game_version' => (string) $request->validated('game_version'),
            'launcher_version' => (string) $request->validated('launcher_version'),
            'device_id' => (string) $request->validated('device_id'),
            'client_nonce' => (string) $request->validated('client_nonce'),
        ], $request->ip());

        return response()->json([
            'payload' => $signed['payload'],
            'signature' => $signed['signature'],
            'key_id' => $signed['key_id'],
        ]);
    }
}
