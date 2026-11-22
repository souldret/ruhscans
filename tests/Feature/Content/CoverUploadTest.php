<?php

use App\Jobs\ProcessSeriesCover;
use App\Models\Series;
use App\Rules\RealMimeImage;
use App\Services\Content\CoverUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

function coverImageBytes(string $color = '#112233', int $width = 320, int $height = 480): string
{
    return (string) (new ImageManager(new Driver))->create($width, $height)->fill($color)->toJpeg();
}

test('geçerli kapak doğrulanır ve kuyruk jobı dispatch edilir', function () {
    Storage::fake('local');
    Storage::fake('r2');
    config(['content.image_disk' => 'local']);

    Queue::fake();

    $file = UploadedFile::fake()->createWithContent('kapak.jpg', coverImageBytes());

    $validator = Validator::make(
        ['cover_path' => $file],
        [
            'cover_path' => [
                'required',
                'file',
                'image',
                'max:'.(int) config('content.max_cover_kilobytes'),
                new RealMimeImage,
            ],
        ],
    );

    expect($validator->fails())->toBeFalse();

    $series = Series::factory()->create(['cover_path' => 'covers/test.jpg']);
    ProcessSeriesCover::dispatch($series->id, 'local', 'covers/test.jpg');

    Queue::assertPushed(ProcessSeriesCover::class);
});

test('10 mb üstü kapak reddedilir', function () {
    $file = UploadedFile::fake()->create('kapak.jpg', 10241, 'image/jpeg');

    $validator = Validator::make(
        ['cover_path' => $file],
        ['cover_path' => ['required', 'file', 'max:'.(int) config('content.max_cover_kilobytes')]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('cover_path'))->toContain('çok büyük');
});

test('çok küçük kapak piksel boyutu reddedilir', function () {
    $file = UploadedFile::fake()->createWithContent('kapak.jpg', coverImageBytes(width: 120, height: 180));

    $validator = Validator::make(
        ['cover_path' => $file],
        ['cover_path' => ['required', 'file', new RealMimeImage]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('cover_path'))->toContain('Kapak çok küçük');
});

test('yanlış mime ve sahte jpg reddedilir', function () {
    $fakeJpg = UploadedFile::fake()->createWithContent('kapak.jpg', '<?php echo 1;');

    $validator = Validator::make(
        ['cover_path' => $fakeJpg],
        ['cover_path' => ['required', 'file', new RealMimeImage]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('cover_path'))->toContain('Desteklenmeyen biçim');

    $gif = UploadedFile::fake()->createWithContent('kapak.gif', (string) (new ImageManager(new Driver))->create(10, 10)->toGif());

    $validator = Validator::make(
        ['cover_path' => $gif],
        ['cover_path' => ['required', 'file', new RealMimeImage]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('cover_path'))->toContain('Desteklenmeyen biçim');
});

test('r2 fake disk ile webp varyantları üretilir ve eski kapak silinir', function () {
    Storage::fake('r2');
    config(['content.image_disk' => 'r2']);

    $series = Series::factory()->create(['cover_path' => 'covers/1/kapak.jpg']);
    Storage::disk('r2')->put('covers/1/kapak.jpg', coverImageBytes());
    Storage::disk('r2')->put('covers/1/kapak-small.jpg', 'eski');
    Storage::disk('r2')->put('covers/1/kapak-placeholder.jpg', 'eski');

    (new ProcessSeriesCover($series->id, 'r2', 'covers/1/kapak.jpg', 'covers/1/kapak.jpg'))->handle();

    $series->refresh();

    expect($series->cover_path)->toBe('covers/'.$series->id.'/cover.webp');

    Storage::disk('r2')->assertExists('covers/'.$series->id.'/cover.webp');
    Storage::disk('r2')->assertExists('covers/'.$series->id.'/cover-small.webp');
    Storage::disk('r2')->assertExists('covers/'.$series->id.'/cover-placeholder.webp');
    Storage::disk('r2')->assertMissing('covers/1/kapak.jpg');
    Storage::disk('r2')->assertMissing('covers/1/kapak-small.jpg');
    Storage::disk('r2')->assertMissing('covers/1/kapak-placeholder.jpg');
});

test('cover url r2 cdn üzerinden üretilir', function () {
    config([
        'content.image_disk' => 'r2',
        'content.cdn_url' => 'cdn.ruhscans.com',
        'filesystems.disks.r2.bucket' => 'test-bucket',
    ]);

    $series = Series::factory()->make(['cover_path' => 'covers/1/cover.webp']);

    expect(app(CoverUrl::class)->for($series))->toBe('https://cdn.ruhscans.com/covers/1/cover.webp')
        ->and(app(CoverUrl::class)->for($series, 'small'))->toBe('https://cdn.ruhscans.com/covers/1/cover-small.webp')
        ->and(app(CoverUrl::class)->for($series, 'placeholder'))->toBe('https://cdn.ruhscans.com/covers/1/cover-placeholder.webp');
});
