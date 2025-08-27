<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Project;

final readonly class PathService
{
    public function __construct(
        private string $rahStoragePath,
    ) {
    }

    public function getProjectPath(Project|string $project): string
    {
        $projectName = $project instanceof Project ? $project->name : $project;
        return $this->rahStoragePath . '/' . $projectName;
    }

    public function getDeploymentPath(Project|string $project, string $name): string
    {
        return $this->getProjectPath($project) . '/' . $name;
    }
}
