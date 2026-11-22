<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChapterZip;
use App\Models\User;
use App\Services\Content\DirectZipUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DirectZipUploadController extends Controller
{
    public function presign(Request $request, DirectZipUpload $upload): JsonResponse
    {
        $data = $request->validate([
            'size' => ['required', 'integer', 'min:1'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $this->authorizeUpload($request);

        try {
            return response()->json($upload->presign((int) $data['size'], (string) $data['filename']));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['size' => $exception->getMessage()]);
        }
    }

    public function startMultipart(Request $request, DirectZipUpload $upload): JsonResponse
    {
        $data = $request->validate([
            'size' => ['required', 'integer', 'min:1'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $this->authorizeUpload($request);

        try {
            return response()->json($upload->startMultipart((int) $data['size'], (string) $data['filename']));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['size' => $exception->getMessage()]);
        }
    }

    public function completeMultipart(Request $request, DirectZipUpload $upload): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:512'],
            'upload_id' => ['required', 'string', 'max:512'],
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.part_number' => ['required', 'integer', 'min:1'],
            'parts.*.etag' => ['required', 'string', 'max:255'],
        ]);

        $this->authorizeUpload($request);
        $this->assertPendingPath($data['path']);

        try {
            $upload->completeMultipart(
                (string) $data['path'],
                (string) $data['upload_id'],
                array_values($data['parts']),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['parts' => $exception->getMessage()]);
        }

        return response()->json(['ok' => true, 'path' => $data['path']]);
    }

    public function complete(Request $request, DirectZipUpload $upload): JsonResponse
    {
        $data = $request->validate([
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'path' => ['required', 'string', 'max:512'],
            'token' => ['required', 'string', 'max:255'],
        ]);

        $this->authorizeUpload($request);
        $this->assertPendingPath($data['path']);

        $reserved = $upload->take((string) $data['token']);

        if ($reserved !== $data['path']) {
            throw ValidationException::withMessages(['token' => 'Yükleme anahtarı geçersiz veya süresi dolmuş.']);
        }

        if (! Storage::disk('r2')->exists($data['path'])) {
            throw ValidationException::withMessages(['path' => 'R2 üzerinde dosya bulunamadı.']);
        }

        ProcessChapterZip::dispatch(
            (int) $data['chapter_id'],
            'r2',
            (string) $data['path'],
            $request->user()->id,
        );

        return response()->json(['ok' => true]);
    }

    private function authorizeUpload(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_CEVIRI]) && ! $user->isBlocked(),
            403,
        );
    }

    private function assertPendingPath(string $path): void
    {
        throw_unless(
            str_starts_with($path, 'chapter-zips/pending/') && ! str_contains($path, '..'),
            ValidationException::withMessages(['path' => 'Geçersiz yükleme yolu.']),
        );
    }
}
