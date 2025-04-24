<?php

namespace App\Controller;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RahBinController extends AbstractController
{
    public function __construct(
        private readonly string $rahHostname,
    ) {
    }

    #[Route('/rah', name: 'app_rah_bin')]
    public function index(Request $request): Response
    {
        if ($request->getHost() !== $this->rahHostname) {
            return $this->forward(FallbackController::class . '::show');
        }

        $file = '/../../public/.rah/rah';
        if (!is_file(__DIR__ . $file)) {
            throw new RuntimeException('rah file not found did you build it locally?');
        }

        return $this->file(__DIR__ . $file, 'rah');
    }
}
