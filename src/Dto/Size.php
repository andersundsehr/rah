<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class Size
{
    public string $humanReadable;

    public function __construct(public int $size)
    {
        $this->humanReadable = self::formatSize($size);
    }

    public static function formatSize(int $size): string
    {
        // round to 2 decimal places for each KB MB GB ..
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
