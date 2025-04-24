<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class UrlService
{
    public function __construct(
        private RequestStack $requestStack,
        private string $rahHostname,
    ) {
    }

    public function getUrl(?string $subdomain = null): string
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

        if (!$subdomain) {
            return $request->getScheme() . '://' . $this->rahHostname . $portPart;
        }

        return $request->getScheme() . '://' . $subdomain . '.' . $this->rahHostname . $portPart;
    }
}
