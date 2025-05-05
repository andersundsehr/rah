<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Deployment;
use App\Dto\Project;
use App\Dto\Settings;
use RuntimeException;
use Safe\DateTimeImmutable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\SerializerInterface;

use function Safe\filemtime;
use function uasort;

final readonly class DeploymentService
{
    public function __construct(
        private UrlService $urlService,
        private FileSizeService $fileSizeService,
        private Filesystem $filesystem,
        private SerializerInterface $serializer,
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

        uasort($deployments, static fn(Deployment $a, Deployment $b): int => $b->lastUpdate <=> $a->lastUpdate);

        return $deployments;
    }

    public function load(Project $project, string $name): Deployment
    {
        $path = $project->path . '/' . $name;
        $url = $this->urlService->getUrl($project->name . '--' . $name);
        $size = $this->fileSizeService->getDirectorySize($path);

        $lastUpdate = new DateTimeImmutable('@' . filemtime($path));

        if (!$this->filesystem->exists($path . '/deployment.json')) {
            $this->filesystem->remove($path);
            throw new RuntimeException('Deployment ' . $name . ' for project ' . $project->name . ' did not have a deployment.json file. The deployment was removed.');
        }

        $file = $this->filesystem->readFile($path . '/deployment.json');

        try {
            $deploymentSettings = $this->serializer->deserialize($file, Settings::class, 'json');
        } catch (MissingConstructorArgumentsException) {
            $this->filesystem->remove($path);
            throw new RuntimeException('Deployment ' . $name . ' for project ' . $project->name . ' did not have a valid deployment.json file. The deployment was removed.');
        }

        return new Deployment($project, $path, $name, $size, $url, $lastUpdate, $deploymentSettings);
    }
}
