<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

use function hash_equals;

final readonly class ApiKeyService
{
    public function __construct(
        private string $rahStoragePath,
        private Filesystem $filesystem,
    ) {
    }

    public function getKey(): string
    {
        $keyFile = $this->getFilename();
        $key = $this->filesystem->readFile($keyFile);
        if (!str_starts_with($key, 'rah_')) {
            throw new RuntimeException('Invalid API key format. in ' . $keyFile);
        }

        return $key;
    }

    public function getFilename(): string
    {
        return $this->rahStoragePath . '/rah-api-key.txt';
    }

    public function createIfNeeded(bool $forced = false): bool
    {
        $keyFile = $this->getFilename();
        if (!$this->filesystem->exists($keyFile) || $forced) {
            $this->filesystem->dumpFile($keyFile, 'rah_' . strtoupper(bin2hex(random_bytes(32))));
            return true;
        }

        return false;
    }

    public function verifyKey(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        return hash_equals($this->getKey(), $token);
    }
}
