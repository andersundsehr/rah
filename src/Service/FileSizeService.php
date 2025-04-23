<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use App\Dto\Size;
use Symfony\Component\Finder\Finder;

final readonly class FileSizeService
{
    public function getDirectorySize(string $path): Size
    {
        $bytesTotal = 0;
        foreach ((new Finder())->files()->in($path) as $file) {
            $bytesTotal += $file->getSize();
        }

        return new Size($bytesTotal);
    }

    /**
     * converts strings like 10G or 20M to bytes
     */
    public function convertToBytes(string $string): int
    {
        $unit = strtolower(substr($string, -1));
        $value = (int)substr($string, 0, -1);
        return match ($unit) {
            'p' => $value * 1024 * 1024 * 1024 * 1024 * 1024,
            't' => $value * 1024 * 1024 * 1024 * 1024,
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => throw new InvalidArgumentException('Invalid size unit provided "' . $string . '"'),
        };
    }
}
