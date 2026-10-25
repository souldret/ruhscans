<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya şifre hatalı.',
            ]);
        }

        $token = $user->createToken('oyun')->plainTextToken;

        return response()->json($this->payload($user, $token));
    }

    public function verify(Request $request): JsonResponse
    {
        $token = $this->bearer($request);

        if ($token === null) {
            return response()->json(['message' => 'Oturum geçersiz.'], 401);
        }

        $minutes = (int) config('sanctum.expiration');
        $createdAt = $token->created_at;

        if ($minutes > 0 && $createdAt instanceof Carbon && $createdAt->lte(now()->subMinutes($minutes))) {
            $token->delete();

            return response()->json(['message' => 'Oturum geçersiz.'], 401);
        }

        $owner = $token->tokenable;

        return response()->json($this->session($owner instanceof User ? $owner : null, true));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->bearer($request)?->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    private function bearer(Request $request): ?PersonalAccessToken
    {
        $plain = $request->bearerToken();

        if (! is_string($plain) || $plain === '') {
            return null;
        }

        $token = PersonalAccessToken::findToken($plain);

        return $token instanceof PersonalAccessToken ? $token : null;
    }

    /**
     * Giriş yanıtı. Eski oyun dosyaları token, vip ve vip_until okur.
     *
     * @return array<string, mixed>
     */
    private function payload(User $user, string $token): array
    {
        return [
            'token' => $token,
            'name' => $user->name,
            ...$this->session($user, false),
        ];
    }

    /**
     * Oturum alanları.
     *
     * vip ve vip_until artık okunmaz. Eski giriş dosyaları bu anahtarlar
     * yoksa çökmesin diye yanıtta durur.
     *
     * @deprecated 2026-10 VIP üyeliği kalktı. Yeni dosyalar coin_balance okusun.
     *
     * @return array<string, mixed>
     */
    private function session(?User $user, bool $verified): array
    {
        $body = [
            'coin_balance' => $user instanceof User ? (int) $user->coin_balance : 0,
            'vip' => false,
            'vip_until' => null,
        ];

        if ($verified) {
            return ['valid' => true] + $body;
        }

        return $body;
    }
}
