<?php

declare(strict_types=1);

namespace App\Dto;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Deployment
{
    private function __construct(
        public readonly Project $project,
        public readonly string $path,
        public readonly string $name,
        public readonly Size $size,
        public readonly string $url,
    ) {}

    public static function fromName(Request $request, Project $project, string $name): self
    {
        // TODO get data from deployment.json
        $path = $project->path . '/' . $name;
        $url = self::getUrl($request, $project->name . '--' . $name);
        return new self($project, $path, $name, self::getDirectorySize($path), $url);
    }

    public static function createFromSettings(Request $request, Settings $settings): self
    {
        $path = '/storage/' . $settings->projectName . '/' . $settings->deployment;
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        return self::fromSettings($request, $settings);
    }

    public static function fromSettings(Request $request, Settings $settings): self
    {
        $project = new Project($request, $settings->projectName);

        if (!is_dir($project->path)) {
            throw new NotFoundHttpException('Project not found: ' . $settings->projectName);
        }

        foreach ($project->deployments as $deployment) {
            if ($deployment->name === $settings->deployment) {
                return $deployment;
            }
        }

        throw new NotFoundHttpException('Deployment not found for ' . $settings->projectName . ' -> ' . $settings->deployment);
    }

    public static function getDirectorySize(string $path): Size
    {
        $bytesTotal = 0;
        $path = realpath($path);
        if ($path && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytesTotal += $object->getSize();
            }
        }
        return new Size($bytesTotal);
    }

    public static function getUrl(Request $request, string $subdomain): string
    {
        $portPart = ':' . $request->getPort();
        if ($request->getScheme() === 'http' && $portPart === ':80') {
            $portPart = '';
        }
        if ($request->getScheme() === 'https' && $portPart === ':443') {
            $portPart = '';
        }
        return $request->getScheme() . '://' . $subdomain . '.' . $_SERVER['RAH_HOSTNAME'] . $portPart;
    }

    public function getData(bool $skipProject = false): array
    {
        $data = (array)$this;
        if ($skipProject) {
            unset($data['project']);
        }
        return $data;
    }
}
