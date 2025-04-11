<?php

namespace App\Controller;

use Composer\InstalledVersions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function getenv;

final class InstallShController extends AbstractController
{
    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire(env: 'RAH_HOSTNAME')]
        private readonly string $rahHostname,
        #[Autowire(env: 'RAH_VERSION')]
        private readonly string $rahVersion,
    ) {
    }

    #[Route('/install.sh', name: 'app_install_sh')]
    public function index(Request $request): Response
    {
        if ($request->getHost() !== $this->rahHostname) {
            return $this->forward(FallbackController::class . '::show');
        }

        $content = $this->filesystem->readFile(__DIR__ . '/../../install.sh');
        $content = str_replace('###RAH_API###', $request->getSchemeAndHttpHost(), $content);
        $content = str_replace('###RAH_AVAILALBE###', $this->rahVersion, $content);

        return new Response($content, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
