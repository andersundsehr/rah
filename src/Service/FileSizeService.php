<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use InvalidArgumentException;
use App\Dto\Size;
use ReflectionClass;
use Symfony\Component\Stopwatch\Stopwatch;

use function Safe\preg_replace;
use function escapeshellarg;
use function explode;
use function is_numeric;
use function shell_exec;
use function trim;

final readonly class FileSizeService
{
    public function __construct(
        private Stopwatch $stopWatch,
        private Filesystem $filesystem,
        private string $rahStoragePath,
    ) {
    }

    public function getDirectorySize(string $path): Size
    {
        return new ReflectionClass(Size::class)->newLazyProxy(fn(): Size => $this->getDirectorySizeReal($path));
    }

    private function getDirectorySizeReal(string $path): Size
    {
        $eventName = preg_replace('/[^a-z0-9]+/i', '-', $path);
        $event = $this->stopWatch->start('file-size-service-get-directory-size-' . $eventName, 'file-size');

        try {
            $output = \Safe\shell_exec('du -sb ' . escapeshellarg($path) . ' 2>&1');
            if (!is_string($output)) {
                throw new InvalidArgumentException('Could not get size of directory: ' . $path);
            }

            $parts = explode("\t", trim($output));

            if (!is_numeric($parts[0])) {
                throw new InvalidArgumentException('Invalid size value received for directory: ' . $path . ' value: ' . $parts[0]);
            }

            $bytesTotal = (int)$parts[0];
        } finally {
            $event->stop();
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

    public function getStorageSize(): Size
    {
        if ($this->filesystem->exists($this->rahStoragePath . '/size-cache.txt')) {
            $bytes = trim($this->filesystem->readFile($this->rahStoragePath . '/size-cache.txt'));
            if (is_numeric($bytes)) {
                return new Size((int)$bytes);
            }
        }

        $size = $this->getDirectorySizeReal($this->rahStoragePath);
        $this->cacheStorageSize($size);
        return $size;
    }

    public function cacheStorageSize(Size $size): void
    {
        $this->filesystem->dumpFile($this->rahStoragePath . '/size-cache.txt', (string)$size->bytes);
    }
}
