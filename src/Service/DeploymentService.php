<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Deployment;
use App\Dto\Project;
use Safe\DateTimeImmutable;
use Symfony\Component\Finder\Finder;

use function Safe\filemtime;

final readonly class DeploymentService
{
    public function __construct(
        private UrlService $urlService,
        private FileSizeService $fileSizeService,
    ) {
    }

    /**
     * @return array<string, Deployment>
     */
    public function loadForProject(Project $project): array
    {
        $deployments = [];
        foreach ((new Finder())->directories()->in($project->path)->depth(0) as $directory) {
            $deploymentName = $directory->getBasename();
            $deployments[$deploymentName] = $this->load($project, $deploymentName);
        }

        return $deployments;
    }

    public function load(Project $project, string $name): Deployment
    {
        // TODO get data from deployment.json
        $path = $project->path . '/' . $name;
        $url = $this->urlService->getUrl($project->name . '--' . $name);
        $size = $this->fileSizeService->getDirectorySize($path);

        $lastUpdate = new DateTimeImmutable('@' . filemtime($path));

        return new Deployment($project, $path, $name, $size, $url, $lastUpdate);
    }
}
