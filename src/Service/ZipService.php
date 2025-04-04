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
        $zip->open($zipFileName, 0);
        if ($zip->getStatusString() !== 'No error') {
            throw new Exception('Failed to open zip file: ' . $zip->getStatusString());
        }

        $tempExtractionFolder = tempnam(sys_get_temp_dir(), 'rah-');
        if(is_file($tempExtractionFolder)){
            unlink($tempExtractionFolder);
        }
        if (!mkdir($tempExtractionFolder) && !is_dir($tempExtractionFolder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $tempExtractionFolder));
        }

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
