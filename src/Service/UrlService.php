<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

final class UrlService
{
    public function __construct(private readonly RequestStack $requestStack) {}

    public function getUrl(string $subdomain): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('No current request available.');
        }

        $portPart = ':' . $request->getPort();
        if ($request->getScheme() === 'http' && $portPart === ':80') {
            $portPart = '';
        }
        if ($request->getScheme() === 'https' && $portPart === ':443') {
            $portPart = '';
        }

        return $request->getScheme() . '://' . $subdomain . '.' . $_SERVER['RAH_HOSTNAME'] . $portPart;
    }
}
