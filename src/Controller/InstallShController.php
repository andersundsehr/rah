<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InstallShController extends AbstractController
{
    #[Route('/install.sh', name: 'app_install_sh', host: RAH_HOSTNAME)]
    public function index(Request $request): Response
    {
        $content = file_get_contents('/app/install.sh');
        $content = str_replace('###RAH_API###', $request->getSchemeAndHttpHost(), $content);
        return new Response($content, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
