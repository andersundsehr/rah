<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class Size
{
    public string $humanReadable;

    public function __construct(public int $bytes)
    {
        $this->humanReadable = self::formatSize($bytes);
    }

    public static function formatSize(int $bytes): string
    {
        $isNegative = $bytes < 0;
        $bytes = abs($bytes);
        // round to 2 decimal places for each KB MB GB ..
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($isNegative ? '-' : '') . round($bytes, 2) . ' ' . $units[$i];
    }
}
