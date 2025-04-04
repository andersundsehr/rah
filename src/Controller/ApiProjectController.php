<?php

namespace App\Controller;

use App\Dto\Deployment;
use App\Dto\Project;
use App\Dto\Settings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
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
        dd($deployment); // TODO delete project

        return $this->json([
            'status' => 'ok',
            'message' => 'deleted',
        ], 200);
    }
}
