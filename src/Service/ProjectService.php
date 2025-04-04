<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Project;
use Symfony\Component\HttpFoundation\Request;

use function explode;
use function str_contains;
use function str_replace;
use function trim;

final readonly class ProjectService
{

    /**
     * @return array{0: string, 1: string}
     */
    public function getProjectParts(Request $request): array
    {
        $part = str_replace($_SERVER['RAH_HOSTNAME'], '', $request->getHost());
        $part = trim(str_replace('.', '', $part), '.');
        if (str_contains($part, '.')) {
            throw new \RuntimeException('Invalid project name : ' . $part);
        }
        [$projectName, $deploymentName] = explode('--', $part . '--', 3);
        return [
            $projectName,
            $deploymentName,
        ];
    }

    public function getAll(Request $request): array
    {
        $projects = [];
        foreach (glob('/storage/*', GLOB_ONLYDIR) as $directory) {
            $projectName = basename($directory);
            $projects[$projectName] = new Project($request, $projectName);
        }
        return $projects;
    }
}
