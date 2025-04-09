<?php

declare(strict_types=1);

namespace App\Service;

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
}
