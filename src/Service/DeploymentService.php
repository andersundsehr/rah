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
use Symfony\Component\Stopwatch\Stopwatch;

use function Safe\filemtime;
use function uasort;

final readonly class DeploymentService
{
    public function __construct(
        private UrlService $urlService,
        private FileSizeService $fileSizeService,
        private Filesystem $filesystem,
        private SerializerInterface $serializer,
        private Stopwatch $stopwatch,
        private PathService $pathService,
    ) {
    }

    /**
     * @return array<string, Deployment>
     */
    public function loadForProject(Project $project): array
    {
        $deployments = [];
        foreach (new Finder()->directories()->in($project->path)->depth(0) as $directory) {
            $deploymentName = $directory->getBasename();

            $event = $this->stopwatch->start('deployment-service-load-' . $project->name . '-' . $deploymentName, 'load');
            try {
                $deployments[$deploymentName] = $this->load($project, $deploymentName);
            } finally {
                $event->stop();
            }
        }

        return $this->sortDeployments($deployments);
    }

    public function load(Project $project, string $name): Deployment
    {
        $path = $this->pathService->getDeploymentPath($project, $name);
        $url = $this->urlService->getUrl($project->name, $name);
        $size = $this->fileSizeService->getDirectorySize($path);

        $lastUpdate = new DateTimeImmutable('@' . filemtime($path));

        if (!$this->filesystem->exists($path . '/deployment.json')) {
            $this->filesystem->remove($path);
            throw new RuntimeException('Deployment ' . $project->name . '--' . $name . ' did not have a deployment.json file. The deployment was removed.');
        }

        $file = $this->filesystem->readFile($path . '/deployment.json');

        try {
            $deploymentSettings = $this->serializer->deserialize($file, Settings::class, 'json');
        } catch (MissingConstructorArgumentsException) {
            $this->filesystem->remove($path);
            throw new RuntimeException(
                'Deployment ' . $project->name . '--' . $name . ' did not have a valid deployment.json file. The deployment was removed.',
            );
        }

        return new Deployment($project, $path, $name, $size, $url, $lastUpdate, $deploymentSettings);
    }

    /**
     * Sort deployments by special names first, then by last update time.
     *
     * @param array<string, Deployment> $deployments
     * @return array<string, Deployment>
     */
    private function sortDeployments(array $deployments): array
    {
        $deploymentOrder = 'main,master,staging,testing,development';
        $specialOrder = array_flip(array_filter(array_map(trim(...), explode(',', $deploymentOrder))));

        uasort($deployments, static function (Deployment $a, Deployment $b) use ($specialOrder): int {
            $orderNumber = $specialOrder[$a->name] ?? $b->lastUpdate->getTimestamp();
            $orderNumber2 = $specialOrder[$b->name] ?? $a->lastUpdate->getTimestamp();
            return $orderNumber <=> $orderNumber2;
        });

        return $deployments;
    }
}
