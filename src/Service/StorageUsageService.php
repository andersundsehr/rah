<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Size;
use App\Dto\StorageUsage;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function Safe\shell_exec;
use function Safe\preg_split;

final readonly class StorageUsageService
{
    private Size $limit;

    public function __construct(
        private string $rahStoragePath,
        string $rahMaxDiskUsage,
        private FileSizeService $fileSizeService,
    ) {
        if (!str_starts_with($this->rahStoragePath, '/')) {
            throw new RuntimeException('RAH_STORAGE_PATH should be an absolute path');
        }

        $this->limit = new Size($this->fileSizeService->convertToBytes($rahMaxDiskUsage));
    }

    public function getDiskUsage(): StorageUsage
    {
        $used = $this->fileSizeService->getDirectorySize($this->rahStoragePath);
        $free = new Size(max($this->limit->bytes - $used->bytes, 0));
        $availableDisk = $this->availableDisk();
        $diskIsFullWarning = $free->bytes > $availableDisk->bytes;
        $percent = 100 / $this->limit->bytes * $used->bytes;
        $percentHuman = sprintf('%.2f%%', $percent);

        return new StorageUsage(
            $this->limit,
            $used,
            $free,
            $availableDisk,
            $diskIsFullWarning,
            $percent,
            $percentHuman,
        );
    }

    private function availableDisk(): Size
    {
        $ex = shell_exec('df ' . escapeshellarg($this->rahStoragePath) . ' 2>&1');
        if ($ex === null) {
            throw new RuntimeException('Could not get disk usage');
        }

        $lines = explode("\n", $ex);
        if (count($lines) < 2) {
            throw new RuntimeException('Could not get disk usage');
        }

        $line = $lines[1];
        $parts = preg_split('/\s+/', $line);
        if (count($parts) < 5) {
            throw new RuntimeException('Could not get disk usage');
        }

        return new Size((int)$parts[3] * 1024);
    }
}
