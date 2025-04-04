<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RahBinController extends AbstractController
{
    #[Route('/rah', name: 'app_rah_bin', host: RAH_HOSTNAME)]
    public function index(): Response
    {
        return $this->file('/app/rah', 'rah');
    }
}
