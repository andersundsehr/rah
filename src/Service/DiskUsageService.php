<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DiskUsageService
{
    public function __construct(
        #[Autowire(env: 'RAH_STORAGE_PATH')]
        private string $storagePath,
    ) {
        if (!str_starts_with($this->storagePath, '/')) {
            throw new RuntimeException('RAH_STORAGE_PATH should be an absolute path');
        }
    }

    /**
     * @return array{percent: string, free: string}
     */
    public function getDiskUsage(): array
    {
        $ex = shell_exec('df -h ' . escapeshellarg($this->storagePath) . ' 2>&1');
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

        return [
            'percent' => $parts[4],
            'free' => $parts[3],
        ];
    }
}
