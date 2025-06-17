<?php

namespace App\Auth;

use App\Attribute\ApiTokenRequired;
use App\Attribute\NoAuthRequiredAtAll;
use App\Service\ApiKeyService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function array_filter;
use function explode;
use function hash_equals;
use function in_array;

final readonly class AuthListener
{
    public function __construct(
        private ?string $rahBasicAuth,
        private ?string $rahAuthIps,
        private ApiKeyService $apiKeyService,
    ) {
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $result = $this->reasonAbout($event);

        if ($result->allowedToCall) {
//            $this->responseWith($event, $result->reason, 200); // debug usage
            return;
        }

        $this->responseWith($event, $result->reason);
    }

    private function reasonAbout(ControllerEvent $event): AuthResult
    {
        if ($event->getAttributes(NoAuthRequiredAtAll::class)) {
            return new AuthResult(true, 'NoAuthRequiredAtAll for Controller ' . $event->getRequest()->get('_controller', '???'));
        }

        if ($event->getAttributes(ApiTokenRequired::class)) {
            return $this->reasonAboutApiToken($event);
        }

        return $this->reasonAboutAuthMethods($event);
    }

    private function reasonAboutApiToken(ControllerEvent $event): AuthResult
    {
        $request = $event->getRequest();
        if ($request->getUser() !== 'api') {
            return new AuthResult(false, 'api user not given');
        }

        $token = $request->getPassword();
        if (!$token) {
            return new AuthResult(false, 'api token not given');
        }

        if (!$this->apiKeyService->verifyKey($token)) {
            return new AuthResult(false, 'api token not valid');
        }

        return new AuthResult(true, 'api token valid');
    }

    private function reasonAboutAuthMethods(ControllerEvent $event): AuthResult
    {

        if (!$this->rahAuthIps && !$this->rahBasicAuth) {
            return new AuthResult(true, 'no auth required');
        }

        $request = $event->getRequest();

        if ($request->getUser() === 'api' && $this->apiKeyService->verifyKey($request->getPassword())) {
            return new AuthResult(true, 'Authenticated by API key');
        }

        if ($this->testBasicAuth($request->getUser(), $request->getPassword())) {
            return new AuthResult(true, 'Authenticated by basic Auth');
        }

        if ($this->testIpAuth($request->getClientIp())) {
            return new AuthResult(true, 'Authenticated by IPAuth');
        }

        $html = 'Access denied 🚩';
        if ($this->rahAuthIps) {
            $html .= '<br>RAH_AUTH_IPS: authentication by IP is enabled, but your IP is not allowed';
        }

        if ($this->rahBasicAuth) {
            $html .= '<br>RAH_BASIC_AUTH: authentication by basic auth is enabled, but your credentials are not valid';
        }

        return new AuthResult(false, $html);
    }

    private function testBasicAuth(?string $givenUser, ?string $givenPassword): bool
    {
        if (!$givenUser || !$givenPassword) {
            return false;
        }

        foreach (array_filter(explode(',', (string)$this->rahBasicAuth)) as $auth) {
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

        $allowedIps = array_filter(explode(',', (string)$this->rahAuthIps));
        if (!$allowedIps) {
            return false;
        }

        if (in_array('private_ranges', $allowedIps, true) && IpUtils::isPrivateIp($clientIp)) {
            return true;
        }

        return IpUtils::checkIp($clientIp, $allowedIps);
    }

    private function responseWith(ControllerEvent $event, string $html, int $status = 401): void
    {
        $headers = [];
        if ($status === 401) {
            $headers = [
                'WWW-Authenticate' => 'Basic realm="api"',
            ];
        }

        $event->setController(fn(): Response => new Response($html, $status, $headers));
    }
}
