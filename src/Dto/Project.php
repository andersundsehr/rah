<?php

declare(strict_types=1);

namespace App\Dto;

use ReflectionClass;
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

    public Size $size;

    public function __construct(
        public string $name,
        public string $path,
        public string $url,
        private ProjectService $projectService,
        private DeploymentService $deploymentService,
    ) {
        $this->deployments = $this->deploymentService->loadForProject($this);
        $this->size = new ReflectionClass(Size::class)->newLazyProxy(function (): Size {
            $size = 0;
            foreach ($this->deployments as $deployment) {
                $size += $deployment->size->bytes;
            }

            return new Size($size);
        });

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
