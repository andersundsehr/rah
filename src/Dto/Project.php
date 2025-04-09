<?php

declare(strict_types=1);

namespace App\Dto;

use App\Service\DeploymentService;
use App\Service\ProjectService;
use JsonSerializable;

final readonly class Project implements JsonSerializable
{
    /** @var array<string, Deployment> */
    public array $deployments;

    public function __construct(
        public string $name,
        public Size $size,
        public string $path,
        public string $url,
        private ProjectService $projectService,
        private DeploymentService $deploymentService,
    ) {
        $this->deployments = $this->deploymentService->loadForProject($this);
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
}
