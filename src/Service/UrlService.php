<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class UrlService
{
    public function __construct(
        private RequestStack $requestStack,
        private string $rahHostname,
        private NameShortingService $nameShortingService,
    ) {
    }

    public function getUrl(?string $projectName = null, ?string $deploymentName = null): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new RuntimeException('No current request available.');
        }

        $portPart = ':' . $request->getPort();
        if ($request->getScheme() === 'http' && $portPart === ':80') {
            $portPart = '';
        }

        if ($request->getScheme() === 'https' && $portPart === ':443') {
            $portPart = '';
        }

        if (!$projectName && !$deploymentName) {
            return $request->getScheme() . '://' . $this->rahHostname . $portPart;
        }

        if (!$projectName) {
            throw new RuntimeException('Project name is required if you pass a deployment name.');
        }

        if ($deploymentName) {
            $subdomain = $this->nameShortingService->createShortName($projectName, $deploymentName);
        } else {
            $subdomain = $this->nameShortingService->hashIfToLong($projectName, NameShortingService::MAX_LABEL_LENGTH);
        }

        return $request->getScheme() . '://' . $subdomain . '.' . $this->rahHostname . $portPart;
    }
}
