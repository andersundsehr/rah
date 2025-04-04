<?php

namespace App\Controller;

use App\Service\ProjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

use function basename;

final class DashboardController extends AbstractController
{
    public function __construct(private FallbackController $fallbackController, private readonly ProjectService $projectService) {}

    #[Route('/', name: 'app_dashboard')]
    public function index(Request $request): Response
    {
        if ($request->getHost() === $_SERVER['RAH_HOSTNAME']) {
            return $this->dashboard($request);
        }

        return $this->projectDashboard($request);
    }

    private function dashboard(Request $request): Response
    {
        return $this->json([
            'projects' => $this->projectService->getAll($request),
        ]);
    }

    private function projectDashboard(Request $request): Response
    {
        [$projectName, $deploymentName] = $this->projectService->getProjectParts($request);

        if ($deploymentName) {
            $response = $this->fallbackController->show($request);
            // TODO should we redirect? or show 404? or forward?
            if ($response->getStatusCode() === 404) {
                return $this->redirect($request->getScheme() . '://' . $projectName . '.' . $_SERVER['RAH_HOSTNAME'] . ':' . $request->getPort());
            }
            return $response;
        }
        if ($projectName === '') {
            return $this->redirect($request->getScheme() . '://' . $_SERVER['RAH_HOSTNAME'] . ':' . $request->getPort());
        }

        return $this->json([
            'project' => $this->projectService->getAll($request)[$projectName] ?? throw new NotFoundHttpException('Project not found: ' . $projectName),
        ]);
    }
}
