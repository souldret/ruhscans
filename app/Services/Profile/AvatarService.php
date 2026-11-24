<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

class AvatarService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function upload(User $user, UploadedFile $file, ?array $crop = null): string
    {
        $realPath = $file->getRealPath();

        if (! $realPath || ! is_file($realPath)) {
            throw new RuntimeException('Yüklenen dosya okunamadı.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($realPath);

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('Yalnızca JPG, PNG veya WebP görsel yüklenebilir.');
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->read($realPath);

        if ($crop && isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
            $w = max(1, min((int) $crop['width'], $image->width()));
            $h = max(1, min((int) $crop['height'], $image->height()));
            $x = max(0, min((int) $crop['x'], max(0, $image->width() - $w)));
            $y = max(0, min((int) $crop['y'], max(0, $image->height() - $h)));
            $image->crop($w, $h, $x, $y);
        }

        $image->cover(256, 256);
        $encoded = (string) $image->toWebp(84);

        $disk = $this->diskName();
        $path = "avatars/{$user->id}/".Str::uuid().'.webp';

        Storage::disk($disk)->put($path, $encoded, [
            'ContentType' => 'image/webp',
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);

        $oldPath = $user->avatar_path;

        $user->forceFill(['avatar_path' => $path])->save();

        if (filled($oldPath) && $oldPath !== $path) {
            $this->deletePath($oldPath);
        }

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->log('Avatar güncellendi.');

        return $path;
    }

    public function remove(User $user): void
    {
        $oldPath = $user->avatar_path;

        if (blank($oldPath)) {
            return;
        }

        $user->forceFill(['avatar_path' => null])->save();
        $this->deletePath($oldPath);

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->log('Avatar kaldırıldı.');
    }

    public function deletePath(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        try {
            Storage::disk($this->diskName())->delete($path);
        } catch (\Throwable) {
            // Eski dosya zaten yoksa sessizce geç.
        }
    }

    public function url(User $user): ?string
    {
        if (blank($user->avatar_path)) {
            return null;
        }

        $disk = $this->diskName();

        if ($disk === 'r2') {
            $cdn = config('content.cdn_url') ?: config('filesystems.disks.r2.url');

            if (filled($cdn)) {
                $base = preg_match('#^https?://#i', (string) $cdn) ? (string) $cdn : 'https://'.$cdn;

                return rtrim($base, '/').'/'.ltrim($user->avatar_path, '/');
            }
        }

        return URL::temporarySignedRoute('profile.avatar', now()->addMinutes(30), [
            'user' => $user->id,
            'v' => substr(md5($user->avatar_path), 0, 8),
        ]);
    }

    public function diskName(): string
    {
        $disk = (string) config('content.image_disk', 'local');

        if ($disk === 'r2' && blank(config('filesystems.disks.r2.bucket'))) {
            return 'local';
        }

        return $disk;
    }
}
