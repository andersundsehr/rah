<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\UrlService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class RedirectController extends AbstractController
{
    public function __construct(private readonly UrlService $urlService)
    {
    }

    #[Route('/redirect', name: 'app_redirect')]
    public function index(
        #[MapQueryParameter] ?string $project = null,
        #[MapQueryParameter] ?string $deployment = null,
        #[MapQueryParameter] string $path = '',
    ): RedirectResponse {
        $path = trim($path, '/');
        if ($path === '.') {
            $path = '';
        }

        return $this->redirect($this->urlService->getUrl($project, $deployment) . ($path ? '/' . $path : ''));
    }
}
