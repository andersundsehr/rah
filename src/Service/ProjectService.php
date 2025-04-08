<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;

use function explode;
use function str_contains;
use function str_replace;
use function trim;

final readonly class ProjectService
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private string $storagePath = '/storage'
         )
    {
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function getProjectParts(string $host): array
    {
        $part = str_replace($_SERVER['RAH_HOSTNAME'], '', $host);
        $part = trim($part, '.');
        if (str_contains($part, '.')) {
            throw new \RuntimeException('Invalid project name : ' . $part);
        }
        [$projectName, $deploymentName] = explode('--', $part . '--', 3);
        return [
            $projectName,
            $deploymentName,
        ];
    }

    public function getAll(Request $request): array
    {
        $projects = [];

        if (!$this->filesystem->exists($this->storagePath)) {
            return $projects;
        }

        foreach (scandir($this->storagePath) as $directory) {
            $fullPath = $this->storagePath . '/' . $directory;
            if ($directory === '.' || $directory === '..' || !$this->filesystem->exists($fullPath) || !is_dir($fullPath)) {
                continue;
            }

            $projectName = basename($directory);
            $projects[$projectName] = new Project($request, $projectName);
        }

        return $projects;
    }
}
