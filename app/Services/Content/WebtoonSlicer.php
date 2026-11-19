<?php

namespace App\Services\Content;

use Intervention\Image\Interfaces\ImageInterface;

class WebtoonSlicer
{
    /**
     * @return list<array{image: ImageInterface, width: int, height: int}>
     */
    public function slice(ImageInterface $image, ?int $maxHeight = null): array
    {
        $maxHeight = $maxHeight ?? (int) config('content.max_page_height');
        $width = $image->width();
        $height = $image->height();

        if ($height <= $maxHeight) {
            return [[
                'image' => $image,
                'width' => $width,
                'height' => $height,
            ]];
        }

        $cuts = $this->cuts($image, $maxHeight);
        $slices = [];
        $start = 0;

        foreach ($cuts as $cut) {
            $sliceHeight = $cut - $start;
            $slices[] = [
                'image' => clone $image,
                'offset' => $start,
                'width' => $width,
                'height' => $sliceHeight,
            ];
            $start = $cut;
        }

        return array_map(function (array $slice) {
            $cropped = $slice['image']->crop($slice['width'], $slice['height'], 0, $slice['offset']);

            return [
                'image' => $cropped,
                'width' => $slice['width'],
                'height' => $slice['height'],
            ];
        }, $slices);
    }

    /**
     * @return list<int>
     */
    private function cuts(ImageInterface $image, int $maxHeight): array
    {
        $height = $image->height();
        $cuts = [];
        $cursor = 0;

        while ($cursor + $maxHeight < $height) {
            $windowStart = $cursor + (int) floor($maxHeight * 0.75);
            $windowEnd = min($height - 1, $cursor + $maxHeight);
            $cuts[] = $this->quietestRow($image, $windowStart, $windowEnd);
            $cursor = $cuts[array_key_last($cuts)];
        }

        $cuts[] = $height;

        return $cuts;
    }

    private function quietestRow(ImageInterface $image, int $start, int $end): int
    {
        if ($end <= $start) {
            return $end;
        }

        $width = $image->width();
        $sampleXs = [0, (int) floor($width / 2), max(0, $width - 1)];
        $best = $end;
        $bestScore = PHP_INT_MAX;

        for ($y = $start; $y <= $end; $y += max(1, (int) floor(($end - $start) / 40))) {
            $score = 0;
            $previous = null;

            foreach ($sampleXs as $x) {
                $color = $image->pickColor($x, $y)->toArray();
                $current = $color[0] + $color[1] + $color[2];
                $score += $previous === null ? 0 : abs($current - $previous);
                $previous = $current;
            }

            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $y;
            }
        }

        return max($start + 1, $best);
    }
}
