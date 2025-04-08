<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

use function dd;
use function is_dir;
use function is_file;
use function mkdir;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final readonly class ZipService {

    public function unzip(string $zipFileName, string $path, bool $append)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFileName, 0) !== true) {
            throw new \RuntimeException('Failed to open the zip file.');
        }

        $tempExtractionFolder = tempnam(sys_get_temp_dir(), 'rah-');
        if(is_file($tempExtractionFolder)){
            unlink($tempExtractionFolder);
        }
        mkdir($tempExtractionFolder);

        $filesystem = new Filesystem();
        try {
            $zip->extractTo($tempExtractionFolder);
            $zip->close();
            $filesystem->mirror($tempExtractionFolder, $path, null, [
                'override' => true,
                'delete' => !$append,
            ]);
        } finally {
            $filesystem->remove($tempExtractionFolder);
        }
    }
}
