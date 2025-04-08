<?php

namespace App\Controller;

use App\Dto\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class ApiProjectController extends AbstractController
{
    #[Route('/api/project', name: 'app_api_project_delete', methods: ['DELETE'])]
    #[Route('/api/project:delete', name: 'app_api_project_delete_get', methods: ['GET'])]
    public function deleteProject(
        Request $request,
        #[MapQueryParameter] string $projectName
    ): JsonResponse
    {
        $project = Project::fromName($request, $projectName);
        
        if (is_dir($project->path)) {
            (new Filesystem())->remove($project->path);
        }

        return $this->json([
            'status' => 'ok',
            'message' => 'deleted',
        ], 200);
    }
}
