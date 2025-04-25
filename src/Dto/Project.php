<?php

declare(strict_types=1);

namespace App\Dto;

use Stringable;
use App\Service\DeploymentService;
use App\Service\ProjectService;
use DateTimeImmutable;
use JsonSerializable;

final readonly class Project implements JsonSerializable, Stringable
{
    /** @var array<string, Deployment> */
    public array $deployments;

    public ?DateTimeImmutable $lastUpdate;

    public function __construct(
        public string $name,
        public Size $size,
        public string $path,
        public string $url,
        private ProjectService $projectService,
        private DeploymentService $deploymentService,
    ) {
        $this->deployments = $this->deploymentService->loadForProject($this);
        $lastUpdate = null;
        foreach ($this->deployments as $deployment) {
            if ($lastUpdate === null || $deployment->lastUpdate > $lastUpdate) {
                $lastUpdate = $deployment->lastUpdate;
            }
        }

        $this->lastUpdate = $lastUpdate;
    }

    public function reload(): self
    {
        return $this->projectService->load($this->name);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $deployments = [];
        foreach ($this->deployments as $deployment) {
            $deployments[$deployment->name] = $deployment->getData();
        }

        return [
            ...(array)$this,
            'deployments' => $deployments,
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
