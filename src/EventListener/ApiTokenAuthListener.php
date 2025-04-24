<?php

namespace App\EventListener;

use App\Attribute\ApiTokenRequired;
use App\Service\ApiKeyService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiTokenAuthListener
{
    public function __construct(private ApiKeyService $apiKeyService)
    {
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->getAttributes(ApiTokenRequired::class)) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getUser() !== 'api') {
            $event->setController(fn(): Response => new Response('api user not given', 401, [
                'WWW-Authenticate' => 'Basic realm="api"',
            ]));
            return;
        }

        $token = $request->getPassword();
        if (!$token) {
            $event->setController(fn(): Response => new Response('api token not given', 401, [
                'WWW-Authenticate' => 'Basic realm="api"',
            ]));
            return;
        }

        if (!$this->apiKeyService->verifyKey($token)) {
            $event->setController(fn(): Response => new Response('api token not valid', 401, [
                'WWW-Authenticate' => 'Basic realm="api"',
            ]));
            return;
        }
    }
}
