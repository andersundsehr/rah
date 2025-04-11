<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Deployment;
use App\Dto\Project;
use App\Dto\Settings;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function explode;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;
use function Safe\shell_exec;
use function Safe\preg_split;

final readonly class ProjectService
{
    public function __construct(
        private Filesystem $filesystem,
        private UrlService $urlService,
        private DeploymentService $deploymentService,
        private FileSizeService $fileSizeService,
        #[Autowire(env: 'RAH_STORAGE_PATH')]
        private string $storagePath,
        #[Autowire(env: 'RAH_HOSTNAME')]
        private string $rahHostname,
    ) {
        if (!str_starts_with($this->storagePath, '/')) {
            throw new RuntimeException('RAH_STORAGE_PATH should be an absolute path');
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function getProjectParts(string $host): array
    {
        if (!str_ends_with($host, $this->rahHostname)) {
            throw new RuntimeException('base domain mismatch: ' . $host . ' does not end with .' . $this->rahHostname);
        }

        $part = str_replace($this->rahHostname, '', $host);
        $part = trim($part, '.');
        if (str_contains($part, '.')) {
            throw new RuntimeException('Invalid project name: ' . $part);
        }

        [$projectName, $deploymentName] = explode('--', $part . '--', 3);
        return [
            $projectName,
            $deploymentName,
        ];
    }

    /**
     * @return array<string, Project>
     */
    public function loadAll(): array
    {
        $projects = [];

        if (!$this->filesystem->exists($this->storagePath)) {
            return $projects;
        }

        foreach ((new Finder())->directories()->in($this->storagePath)->depth(0) as $directory) {
            $projectName = $directory->getBasename();
            $projects[$projectName] = $this->load($projectName);
        }

        uasort($projects, fn(Project $a, Project $b): int => $b->lastUpdate <=> $a->lastUpdate);

        return $projects;
    }

    public function load(string $name): Project
    {
        $path = $this->storagePath . '/' . $name;

        if (!$this->filesystem->exists($path)) {
            throw new NotFoundHttpException('Project not found: ' . $name);
        }

        $url = $this->urlService->getUrl($name);
        $size = $this->fileSizeService->getDirectorySize($path);

        // TODO load project.json

        return new Project($name, $size, $path, $url, $this, $this->deploymentService);
    }

    public function create(string $projectName): Project
    {
        $this->filesystem->mkdir($this->storagePath . '/' . $projectName);

        return $this->load($projectName);
    }

    public function loadDeploymentFromSettings(Settings $settings): Deployment
    {
        $project = $this->load($settings->projectName);

        return $project->deployments[$settings->deployment] ?? throw new NotFoundHttpException('Deployment not found: ' . $settings->projectName . '--' . $settings->deployment);
    }

    public function createDeploymentFromSettings(Settings $settings): Deployment
    {
        $project = $this->create($settings->projectName);

        $this->filesystem->mkdir($project->path . '/' . $settings->deployment);

        return $this->deploymentService->load($project->reload(), $settings->deployment);
    }

    /**
     * @return array{percent: float, free: string}
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
