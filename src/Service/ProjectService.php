<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Deployment;
use App\Dto\Project;
use App\Dto\Settings;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function explode;
use function json_encode;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final readonly class ProjectService
{
    public function __construct(
        private Filesystem $filesystem,
        private UrlService $urlService,
        private DeploymentService $deploymentService,
        private FileSizeService $fileSizeService,
        private string $rahStoragePath,
        private string $rahHostname,
    ) {
        if (!str_starts_with($this->rahStoragePath, '/')) {
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

        if (!$this->filesystem->exists($this->rahStoragePath)) {
            return $projects;
        }

        foreach ((new Finder())->directories()->in($this->rahStoragePath)->depth(0) as $directory) {
            $projectName = $directory->getBasename();
            $project = $this->load($projectName);
            if (!$project->deployments) {
                $this->filesystem->remove($project->path);
                continue;
            }

            $projects[$projectName] = $project;
        }

        uasort($projects, fn(Project $a, Project $b): int => $b->lastUpdate <=> $a->lastUpdate);

        return $projects;
    }

    public function load(string $name): Project
    {
        $path = $this->rahStoragePath . '/' . $name;

        if (!$this->filesystem->exists($path)) {
            throw new NotFoundHttpException('Project not found: ' . $name);
        }

        $url = $this->urlService->getUrl($name);
        $size = $this->fileSizeService->getDirectorySize($path);

        return new Project($name, $size, $path, $url, $this, $this->deploymentService);
    }

    public function create(string $projectName): Project
    {
        $this->filesystem->mkdir($this->rahStoragePath . '/' . $projectName);
        $this->filesystem->touch($this->rahStoragePath . '/' . $projectName);

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
        $this->filesystem->touch($project->path . '/' . $settings->deployment);

        $content = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->filesystem->dumpFile($project->path . '/' . $settings->deployment . '/deployment.json', $content);

        return $this->deploymentService->load($project->reload(), $settings->deployment);
    }

    public function deleteDeployment(Deployment $deploymentToDelete): void
    {
        $this->filesystem->remove($deploymentToDelete->path);
    }
}
