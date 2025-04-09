<?php

namespace App\Controller;

use App\Service\ProjectService;
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
        #[Autowire(env: 'RAH_HOSTNAME')]
        private string $rahHostname,
    ) {}

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
        return $this->json([
            'status' => 'TODO add nice Dashboard',
            'projects' => $this->projectService->loadAll(), // TODO add nice Dashboard
        ]);
    }

    private function projectDashboard(Request $request): Response
    {
        [$projectName, $deploymentName] = $this->projectService->getProjectParts($request->getHost());

        $statusCode = 200;

        if ($deploymentName) {
           $response = $this->forward('App\Controller\FallbackController::show');

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

        return $this->json([
            'status' => 'TODO add nice Dashboard',
            'project' => $project, // TODO add nice Dashboard
        ], $statusCode);
    }
}
