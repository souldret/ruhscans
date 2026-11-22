<?php

use App\Http\Controllers\Admin\DirectZipUploadController;
use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\User;
use App\Services\Content\DirectZipUpload;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('doğrudan yükleme eşiğin altındaki boyutu reddeder', function () {
    $service = app(DirectZipUpload::class);

    expect(fn () => $service->presign(1024, 'bolum.zip'))
        ->toThrow(RuntimeException::class, 'normal ZIP yükleme alanını kullanın');
});

test('token bir kez tüketilir', function () {
    $service = app(DirectZipUpload::class);
    $path = 'chapter-zips/pending/test.zip';

    $token = $service->reserve($path);

    expect($service->take($token))->toBe($path)
        ->and($service->take($token))->toBeNull();
});

test('yetkisiz kullanıcı doğrudan yükleme endpointine erişemez', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_UYE);

    $this->actingAs($user)
        ->post(route('admin.zip.direct.presign'), [
            'size' => 120000 * 1024,
            'filename' => 'bolum.zip',
        ])
        ->assertForbidden();
});

test('geçerli token ve r2 dosyası jobı kuyruğa alır', function () {
    Storage::fake('r2');
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);
    $chapter = Chapter::factory()->create();

    $path = 'chapter-zips/pending/test.zip';
    Storage::disk('r2')->put($path, 'zip');
    $token = app(DirectZipUpload::class)->reserve($path);

    $this->actingAs($user->fresh())
        ->post(route('admin.zip.direct.complete'), [
            'chapter_id' => $chapter->id,
            'path' => $path,
            'token' => $token,
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    Queue::assertPushed(ProcessChapterZip::class, function (ProcessChapterZip $job) use ($chapter, $path) {
        return $job->chapterId === $chapter->id
            && $job->zipDisk === 'r2'
            && $job->zipPath === $path;
    });
});

test('geçersiz token jobı kuyruğa almaz', function () {
    Storage::fake('r2');
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);
    $chapter = Chapter::factory()->create();

    $path = 'chapter-zips/pending/test.zip';
    Storage::disk('r2')->put($path, 'zip');

    $this->actingAs($user->fresh())
        ->postJson(route('admin.zip.direct.complete'), [
            'chapter_id' => $chapter->id,
            'path' => $path,
            'token' => 'yanlis-token',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    Queue::assertNothingPushed();
});

test('doğrudan yükleme rotaları auth altında tanımlı', function () {
    expect(Route::has('admin.zip.direct.presign'))->toBeTrue()
        ->and(Route::has('admin.zip.direct.multipart.start'))->toBeTrue()
        ->and(Route::has('admin.zip.direct.multipart.complete'))->toBeTrue()
        ->and(Route::has('admin.zip.direct.complete'))->toBeTrue();
});
