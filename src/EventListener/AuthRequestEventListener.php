<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function array_filter;
use function explode;
use function in_array;

final readonly class AuthRequestEventListener
{
    public function __construct(
        #[Autowire(env: 'RAH_BASIC_AUTH')]
        private string $rahBasicAuth,
        #[Autowire(env: 'RAH_AUTH_IPS')]
        private string $rahAuthIps,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->rahAuthIps && !$this->rahBasicAuth) {
            // no auth required
            return;
        }

        $request = $event->getRequest();
        if ($this->testBasicAuth($request->getUser(), $request->getPassword())) {
            // Authenticated by basic Auth
            return;
        }

        if ($this->testIpAuth($request->getClientIp())) {
            // Authenticated by IPAuth
            return;
        }

        $html = 'Access denied 🚩';
        if ($this->rahAuthIps) {
            $html .= '<br>RAH_AUTH_IPS: authentication by IP is enabled, but your IP is not allowed';
        }

        if ($this->rahBasicAuth) {
            $html .= '<br>RAH_BASIC_AUTH: authentication by basic auth is enabled, but your credentials are not valid';
        }

        $event->setResponse(new Response($html, Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Basic realm="Restricted Area"',
        ]));
    }

    private function testBasicAuth(?string $givenUser, ?string $givenPassword): bool
    {
        if (!$givenUser || !$givenPassword) {
            return false;
        }

        foreach (array_filter(explode(',', $this->rahBasicAuth)) as $auth) {
            if (hash_equals($auth, $givenUser . ':' . $givenPassword)) {
                return true;
            }
        }

        return false;
    }

    private function testIpAuth(?string $clientIp): bool
    {
        if (!$clientIp) {
            return false;
        }

        $allowedIps = array_filter(explode(',', $this->rahAuthIps));
        if (!$allowedIps) {
            return false;
        }

        if (in_array('private_ranges', $allowedIps, true) && IpUtils::isPrivateIp($clientIp)) {
            return true;
        }

        return IpUtils::checkIp($clientIp, $allowedIps);
    }
}
