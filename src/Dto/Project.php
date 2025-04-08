<?php

declare(strict_types=1);

namespace App\Dto;

use JsonSerializable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function basename;
use function glob;

use const GLOB_ONLYDIR;

final class Project implements JsonSerializable
{
    private static string $basePath = '/storage';

    public static function setBasePath(string $path): void
    {
        self::$basePath = $path;
    }

    /** @var array<string, Deployment> */
    public readonly array $deployments;
    public readonly Size $size;
    public readonly string $path;
    public readonly string $url;

    public function __construct(
        Request $request,
        public readonly string $name,

    ) {
        $this->path = self::$basePath . '/' . $this->name;
        $this->url = Deployment::getUrl($request, $this->name);
        $this->size = Deployment::getDirectorySize($this->path);

        // TODO get default deployment from project.json

        $deployments = [];
        foreach (glob(self::$basePath . '/' . $this->name . '/*', GLOB_ONLYDIR) as $filename) {
            $deploymentName = basename($filename);
            $deployments[$deploymentName] = Deployment::fromName($request, $this, $deploymentName);
        }
        $this->deployments = $deployments;
    }

    public static function fromName(Request $request, string $name): self
    {
        $project = new self($request, $name);
        if (!is_dir($project->path)) {
            throw new NotFoundHttpException('Project not found: ' . $name);
        }
        return $project;
    }

    public function jsonSerialize(): array
    {
        $deployments = [];
        foreach ($this->deployments as $deployment) {
            $deployments[$deployment->name] = $deployment->getData(true);
        }
        return [
            ...(array)$this,
            'deployments' => $deployments,
        ];
    }
}
