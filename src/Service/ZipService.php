<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

use function sys_get_temp_dir;
use function tempnam;

final readonly class ZipService {

    public function __construct(private Filesystem $filesystem) {}

    public function unzip(string $zipFileName, string $path, bool $append): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFileName, 0) !== true) {
            throw new \RuntimeException('Failed to open the zip file.');
        }

        $tempExtractionFolder = $this->filesystem->tempnam(sys_get_temp_dir(), 'rah-');
        $this->filesystem->remove($tempExtractionFolder); // sometimes it is created as file
        $this->filesystem->mkdir($tempExtractionFolder);

        try {
            $zip->extractTo($tempExtractionFolder);
            $zip->close();
            $this->filesystem->mirror($tempExtractionFolder, $path, null, [
                'override' => true,
                'delete' => !$append,
            ]);
        } finally {
            $this->filesystem->remove($tempExtractionFolder);
        }
    }
}
