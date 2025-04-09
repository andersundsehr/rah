<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RahBinController extends AbstractController
{

    public function __construct(
        #[Autowire(env: 'RAH_HOSTNAME')]
        private readonly string $rahHostname,
    ) {}

    #[Route('/rah', name: 'app_rah_bin')]
    public function index(Request $request): Response
    {
        if ($request->getHost() !== $this->rahHostname) {
            return $this->forward('App\Controller\FallbackController::show');
        }
        return $this->file('/app/rah', 'rah');
    }
}
