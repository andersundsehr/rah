<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class Deployment
{
    public function __construct(
        public Project $project,
        public string $path,
        public string $name,
        public Size $size,
        public string $url,
    ) {}


    public function reload(): Deployment
    {
        $newProject = $this->project->reload();
        return $newProject->deployments[$this->name] ?? throw new NotFoundHttpException('Deployment not found, after reload: ' . $this->project->name . '--' . $this->name);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = (array)$this;
        unset($data['project']);
        return $data;
    }
}
