<?php

namespace App\Controller;

use App\Attribute\ApiTokenRequired;
use App\Service\ProjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ApiProjectController extends AbstractController
{
    public function __construct(private readonly ProjectService $projectService)
    {
    }

    #[Route('/api/project', name: 'app_api_project_delete', methods: ['DELETE'])]
    #[Route('/api/project:delete', name: 'app_api_project_delete_get', methods: ['GET'])]
    #[ApiTokenRequired]
    public function deleteProject(
        #[MapQueryParameter] string $projectName
    ): JsonResponse {
        try {
            $project = $this->projectService->load($projectName);
        } catch (NotFoundHttpException) {
            return $this->json([
                'status' => 'ok',
                'message' => 'already deleted',
            ], 200);
        }

        if (is_dir($project->path)) {
            (new Filesystem())->remove($project->path);
        }

        return $this->json([
            'status' => 'ok',
            'message' => 'deleted',
        ], 200);
    }
}
