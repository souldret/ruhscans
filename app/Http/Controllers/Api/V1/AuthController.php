<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\LauncherLoginLog;
use App\Models\User;
use App\Services\Launcher\LoginThrottle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginThrottle $throttle): JsonResponse
    {
        $email = $request->string('email')->toString();
        $user = User::withTrashed()->where('email', $email)->first();
        $ip = $request->ip();

        if ($throttle->tooMany($ip ?? '', $user)) {
            $this->logLogin($user, $request, 'rate_limited', 'too_many');

            return response()->json([
                'message' => 'Çok fazla giriş denemesi. Bir dakika sonra tekrar dene.',
            ], 429);
        }

        $locked = $throttle->blocked($user, $ip ?? '');

        if ($locked !== null) {
            $this->logLogin($user, $request, 'locked', 'temporary_lock');

            throw ValidationException::withMessages([
                'email' => $locked,
            ]);
        }

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            $lock = $throttle->recordFailure($user, $ip ?? '');
            $this->logLogin($user, $request, $lock !== null ? 'locked' : 'denied', $lock !== null ? 'temporary_lock' : 'bad_credentials');

            throw ValidationException::withMessages([
                'email' => $lock ?? 'E-posta veya şifre hatalı.',
            ]);
        }

        if (($user->getAttributes()['deleted_at'] ?? null) !== null) {
            $this->logLogin($user, $request, 'denied', 'banned');

            throw ValidationException::withMessages([
                'email' => 'Bu hesap kapatıldı. Giriş yapılamaz.',
            ]);
        }

        $throttle->clear($user, $ip ?? '');
        $user->tokens()->delete();
        $token = $user->createToken('oyun')->plainTextToken;
        $this->logLogin($user, $request, 'allowed', 'ok');

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

        if ($owner instanceof User && ($owner->getAttributes()['deleted_at'] ?? null) !== null) {
            return response()->json(['message' => 'Bu hesap kapatıldı. Giriş yapılamaz.'], 403);
        }

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

    private function logLogin(?User $user, Request $request, string $result, string $reason): void
    {
        $ip = $request->ip();

        LauncherLoginLog::query()->create([
            'user_id' => $user?->id,
            'game_id' => null,
            'device_id' => $request->string('device_id')->toString() ?: null,
            'ip_hash' => is_string($ip) && $ip !== '' ? hash('sha256', $ip.'|'.(string) config('app.key')) : null,
            'result' => $result,
            'reason' => $reason,
        ]);
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
