<?php

namespace App\Controller;

use App\Attribute\ApiTokenRequired;
use App\Service\StorageUsageService;
use App\Service\ProjectService;
use App\Service\UrlService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

use function basename;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly string $rahHostname,
        private readonly UrlService $urlService,
        private readonly StorageUsageService $diskUsageService,
    ) {
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(Request $request): Response
    {
        if ($request->getHost() === $this->rahHostname) {
            return $this->dashboard();
        }

        return $this->projectDashboard($request);
    }

    private function dashboard(): Response
    {
        return $this->render('dashboard.html.twig', [
            'dashboardUrl' => $this->urlService->getUrl(),
            'projects' => $this->projectService->loadAll(),
            'diskUsage' => $this->diskUsageService->getDiskUsage(),
        ]);
    }

    private function projectDashboard(Request $request): Response
    {
        [$projectName, $deploymentName] = $this->projectService->getProjectParts($request->getHost());

        $statusCode = 200;

        if ($deploymentName) {
            $response = $this->forward(FallbackController::class . '::show');

            if ($response->getStatusCode() !== 404) {
                return $response;
            }

            // fall through to show the project dashboard or main dashboard
            $statusCode = 404;
        }

        try {
            $project = $this->projectService->load($projectName);
        } catch (NotFoundHttpException) {
            $response = $this->dashboard();
            $response->setStatusCode(404);
            return $response;
        }

        $response = $this->render('dashboard-project.html.twig', [
            'dashboardUrl' => $this->urlService->getUrl(),
            'project' => $project,
            'diskUsage' => $this->diskUsageService->getDiskUsage(),
        ]);
        $response->setStatusCode($statusCode);
        return $response;
    }
}
