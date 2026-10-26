<?php

namespace Tests\Feature;

use App\Models\GameFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFileSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_hundred_megabytes_reads_as_one_hundred_megabytes(): void
    {
        $file = GameFile::factory()->create(['size_bytes' => 100 * 1048576]);

        $this->assertSame('100 MB', $file->sizeLabel());
    }

    public function test_a_small_file_is_not_rounded_to_zero(): void
    {
        $file = GameFile::factory()->create(['size_bytes' => 512 * 1024]);

        $this->assertSame('512 KB', $file->sizeLabel());
    }
}
