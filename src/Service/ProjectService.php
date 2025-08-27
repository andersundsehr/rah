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
use Symfony\Component\Stopwatch\Stopwatch;

use function array_values;
use function explode;
use function json_encode;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;
use function uasort;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final readonly class ProjectService
{
    public function __construct(
        private Filesystem $filesystem,
        private UrlService $urlService,
        private DeploymentService $deploymentService,
        private string $rahStoragePath,
        private string $rahHostname,
        private NameShortingService $nameShortingService,
        private Stopwatch $stopwatch,
        private PathService $pathService,
    ) {
        if (!str_starts_with($this->rahStoragePath, '/')) {
            throw new RuntimeException('RAH_STORAGE_PATH should be an absolute path');
        }
    }

    public function getProjectOrDeploymentFromHostname(string $host): Project|Deployment|null
    {
        if (!str_ends_with($host, $this->rahHostname)) {
            throw new RuntimeException('base domain mismatch: ' . $host . ' does not end with .' . $this->rahHostname);
        }

        $part = str_replace($this->rahHostname, '', $host);
        $part = trim($part, '.');
        if (str_contains($part, '.')) {
            throw new RuntimeException('Invalid project name (no dots allowed): ' . $part);
        }

        if (count(explode('--', $part)) > 2) {
            throw new RuntimeException('Invalid project name (only one -- allowed): ' . $part);
        }

        [$projectPart, $deploymentPart] = explode('--', $part . '--', 3);

        $project = $this->findProject($projectPart);
        if (!$project) {
            return null;
        }

        return $this->findDeployment($project, $deploymentPart) ?? $project;
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
        $event = $this->stopwatch->start('project-service-load-all', 'load');

        try {
            $projects = [];

            if (!$this->filesystem->exists($this->rahStoragePath)) {
                $event->stop();
                return $projects;
            }

            foreach (new Finder()->directories()->in($this->rahStoragePath)->depth(0) as $directory) {
                $event->lap();

                $projectName = $directory->getBasename();

                $project = $this->load($projectName);
                if (!$project->deployments) {
                    $this->filesystem->remove($this->pathService->getProjectPath($project));
                    continue;
                }

                $projects[$projectName] = $project;
            }

            uasort($projects, fn(Project $a, Project $b): int => $b->lastUpdate <=> $a->lastUpdate);
        } finally {
            $event->stop();
        }

        return $projects;
    }

    public function load(string $name): Project
    {
        $event = $this->stopwatch->start('project-service-load-' . $name, 'load');

        try {
            $path = $this->pathService->getProjectPath($name);
            if (!$this->filesystem->exists($path)) {
                throw new NotFoundHttpException('Project not found: ' . $name);
            }

            $url = $this->urlService->getUrl($name);
            return new Project($name, $path, $url, $this, $this->deploymentService);
        } finally {
            $event->stop();
        }
    }

    public function create(string $projectName): Project
    {
        $this->filesystem->mkdir($this->pathService->getProjectPath($projectName));
        $this->filesystem->touch($this->pathService->getProjectPath($projectName));

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

        $deploymentPath = $this->pathService->getDeploymentPath($project->name, $settings->deployment);
        $this->filesystem->mkdir($deploymentPath);
        $this->filesystem->touch($deploymentPath);

        $content = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->filesystem->dumpFile($deploymentPath . '/deployment.json', $content);

        return $this->deploymentService->load($project->reload(), $settings->deployment);
    }

    public function deleteDeployment(Deployment $deploymentToDelete): void
    {
        $this->filesystem->remove($deploymentToDelete->path);
    }

    private function findProject(string $projectPart): ?Project
    {
        return $this->nameShortingService->findObjectByName($projectPart, ...array_values($this->loadAll()));
    }

    private function findDeployment(Project $project, string $deploymentPart): ?Deployment
    {
        return $this->nameShortingService->findObjectByName($deploymentPart, ...array_values($project->deployments));
    }
}
